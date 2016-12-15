<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

use App\Models\Application;
use App\Models\Question;
use App\Models\Criteria;
use App\Models\Judged;
use App\Models\User;
use App\Models\Score;
use App\Models\Round;

use App\Http\Requests\ApplicationRequest;
use App\Events\ApplicationSubmitted;
use App\Events\ApplicationChanged;

class ApplicationController extends Controller
{
    public function listApplications()
    {
        if($this->auth->check())
        {
            if(in_array($this->auth->user()->role, ['admin']))
            {
                $applications = Application::orderBy('updated_at', 'desc')->get();
            }
            elseif(in_array($this->auth->user()->role, ['judge', 'observer']))
            {
                $applications = Application::whereIn('status', ['submitted', 'review'])->orderBy('updated_at', 'desc')->get();
            }
            else
            {
                // otherwise redirect to home page? (normal users see a list in their dashboard)
                return redirect('');
            }

            return view('pages/applications/list', compact('applications'));
        }
        else
        {
            return redirect('');
        }

    }

    public function createApplication(ApplicationRequest $request)
    {
        // Double check to make sure the current user is authorized to do this...
        $this->authorize('create-application');

        // Only allow applications to be created if there is an ongoing funding round
        $ongoing = Round::ongoing();

        if(!$ongoing->count())
        {
            $request->session()->flash('error', 'Sorry, new applications cannot be created at this time.');
            return redirect('/');
        }

        // Generate a new application, assign required information
        $application = new Application;
        $application->status = "new";
        $application->user_id = Auth::user()->id;
        $application->round_id = $ongoing->first()->id;
        $application->save();

        // Assign user input
        $input = $request->all();
        $application->update($input);

        $request->session()->flash('success', 'Your application has been created.');
        return redirect('/applications/' . $application->id);
    }

    public function createApplicationForm(Request $request)
    {
        // Only allow applications to be created if there is an ongoing funding round
        $ongoing = Round::ongoing();

        if(!$ongoing->count())
        {
            $request->session()->flash('error', 'Sorry, new applications cannot be created at this time.');
            return redirect('/');
        }

        return view('pages/applications/create');
    }

    public function viewApplication(Application $application, Request $request)
    {
        // Did the current user create this application?
        if($application->user->id != Auth::user()->id)
        {
            // If not, are they authorized to view applications?
            $this->authorize('view-application');
        }

        if($application->status != 'new')
        {
            $request->session()->flash('error', 'Your application has been submitted, you may no longer make changes.');
            return redirect('/applications/' . $application->id . '/review');
        }

        $questions = Question::get();
        
        // Generate an array of answers based on their associated question ID
        $answers = [];

        foreach($application->answers as $answer)
        {
            $answers[$answer->question_id] = $answer;
        }

        return view('pages/applications/view', compact('application', 'questions', 'answers'));
    }

    public function updateApplication(Application $application, ApplicationRequest $request)
    {
        // Did the current user create this application?
        if($application->user->id != Auth::user()->id)
        {
            $request->session()->flash('error', 'Only the person who created an application may change it.');
            return redirect('/login');
        }

        if($application->status != 'new')
        {
            $request->session()->flash('error', 'Your application has been submitted, you may no longer make changes.');
            return redirect('/applications/' . $application->id . '/review');
        }

        $input = $request->all();
        $application->update($input);

        $request->session()->flash('success', 'Your application has been updated.');
        return redirect('/applications/' . $application->id);
    }

    public function reviewApplication(Application $application, Request $request)
    {
        // Did the current user create this application?
        if($application->user->id != Auth::user()->id)
        {
            // If not, are they authorized to view applications?
            if(!(Auth::user()->can('view-application') || (Auth::user()->can('view-submitted-application') && $application->status != 'new')))
            {
                $request->session()->flash('error', 'You are not authorized to view this application.');
                return redirect('/login');
            }
        }

        $questions = Question::get();
        $criteria =
        [
            'objective' => Criteria::where('type', 'objective')->get(),
            'subjective' => Criteria::where('type', 'subjective')->get(),
        ];
        
        // Generate an array of answers based on their associated question ID
        $answers = [];
        
        foreach($application->answers as $answer)
        {
            $answers[$answer->question_id] = $answer;
        }

        $scores = [];

        // Select all scores made by the current user
        foreach($application->scores()->where('user_id', Auth::user()->id)->get() as $score)
        {
            $scores[$score->criteria_id] = $score;
        }

        $judged = Judged::where(['application_id' => $application->id, 'user_id' => Auth::user()->id])->get()->first();

        return view('pages/applications/review', compact('application', 'questions', 'answers', 'criteria', 'scores', 'judged'));
    }

    // Helper function for checking if all required answers are filled in
    private function missingRequiredAnswers($application)
    {
        // Generate an array of answers based on their associated question ID
        $answers = [];

        foreach($application->answers as $answer)
        {
            $answers[$answer->question_id] = $answer;
        }

        // Loop through all required questions
        $questions = Question::where('required', 1)->get();

        foreach($questions as $question)
        {
            if(!isset($answers[$question->id]))
                return true;

            if(empty($answers[$question->id]->answer))
                return true;
        }

        return false;
    }

    // This function is called when a user submits their application
    public function submitApplication(Application $application, Request $request)
    {
        // Did the current user create this application?
        if($application->user->id != Auth::user()->id)
        {
            $request->session()->flash('error', 'Only the person who created an application may submit it.');
            return redirect('/login');
        }

        // Prevent submitting applications when a round is not currently ongoing
        if($application->round->status() != 'ongoing')
        {
            $request->session()->flash('error', 'Sorry, you missed the deadline for submitting your application.');
            return redirect('/');
        }

        if($this->missingRequiredAnswers($application))
        {
            $request->session()->flash('error', 'Your application is missing required information.');
            return redirect('/applications/' . $application->id . '/review');
        }

        if($application->status != 'new')
        {
            $request->session()->flash('error', 'Your application has been submitted, you may no longer make changes.');
            return redirect('/applications/' . $application->id . '/review');
        }

        $application->status = 'submitted';
        $application->save();

        // Send notification to judges
        event(new ApplicationSubmitted($application));

        $request->session()->flash('success', 'Thank you, your application has been submitted and will be reviewed by our judges.');
        return redirect('/');
    }

    // This function is called when a user withdraw their application
    // They may withdraw during or after the round has ended (but before it is finalized)
    // TODO - add withdrawn to the status field migration.
    public function withdrawApplication(Application $application, Request $request)
    {
        // Did the current user create this application?
        if($application->user->id != Auth::user()->id)
        {
            $request->session()->flash('error', 'Only the person who created an application may witdraw it.');
            return redirect('/login');
        }

        if($application->status != 'submitted')
        {
            $request->session()->flash('error', 'Your application is not submitted, thus you do not need to withdraw it.');
            return redirect('/applications/' . $application->id );
        }

        $application->status = 'new'; // TODO 'withdawn';
        $application->save();

        // Send notification to judges? Does it matter if a judge knows it is withdrawn?
        // event(new ApplicationSubmitted($application));

        $request->session()->flash('success', 'Warning! Your application has been withdrawn. It will not be judged. You must submit it to be reviewed by our judges.');
        return redirect('/');
    }

    // This function is called when a judge submits their scores for an application
    public function judgeApplication(Application $application, Request $request)
    {
        // Check if current user is allowed to score things
        $this->authorize('score-application');

        if($application->round->status() != 'ended')
        {
            $request->session()->flash('error', 'Please wait until the grant round is over before judging an application.');
            return redirect('/applications/' . $application->id . '/review');
        }

        if(!in_array($application->judge_status, ['new', 'ready']))
        {
            $request->session()->flash('error', 'This application has already been finalized.');
            return redirect('/applications/' . $application->id . '/review');
        }

        // Check if the current user has already judged this application
        $judged = Judged::where(['application_id' => $application->id, 'user_id' => Auth::user()->id])->get()->first();

        if(!empty($judged))
        {
            $request->session()->flash('error', 'You have already judged this application!');
            return redirect('/applications');
        }
        else
        {
            $judged = new Judged;
            $judged->application_id = $application->id;
            $judged->user_id = Auth::user()->id;
            $judged->save();

            // Compare the total number of judges vs number of judges who have scored this application
            $totalJudges = User::where(['role' => 'judge'])->get();
            $totalJudged = Judged::where(['application_id' => $application->id])->get();

            // If at least half of the judges have rated this application, set it to ready
            if(ceil($totalJudged->count() / $totalJudges->count()) >= 0.5)
            {
                $application->judge_status = 'ready';
                $application->save();
            }

            // Calculate new scores
            Score::calculateTotals($application);

            $request->session()->flash('success', 'Your final scores have been submitted.');
            return redirect('/applications');
        }
    }

    // Function for admins to approve applications
    function approveApplication(Application $application, Request $request)
    {
        // Check if current user has permission
        $this->authorize('approve-application');

        $application->status = 'accepted';
        $application->judge_status = 'finalized';
        $application->save();

        // Send notification to judges and applicant
        event(new ApplicationChanged($application));

        $request->session()->flash('success', 'This application has been approved.');
        return redirect('/applications');
    }

    // Function for admins to deny applications
    function denyApplication(Application $application, Request $request)
    {
        // Check if current user has permission
        $this->authorize('approve-application');

        $application->status = 'rejected';
        $application->judge_status = 'finalized';
        $application->save();

        // Send notification to judges and applicant
        event(new ApplicationChanged($application));

        $request->session()->flash('success', 'This application has been denied.');
        return redirect('/applications');
    }
}
