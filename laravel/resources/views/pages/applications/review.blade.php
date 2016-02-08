@extends('app')

@section('content')
    <h1>Revew Application</h1>
    <hr>

    <h2>Basic Information</h2>

    {!! Form::open(['url' => "applications/{$application->id}/submit"]) !!}
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Question</th>
                    <th>Answer</th>
                    <th class="button">Required</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td><b>Name of Your Project</b></td>
                    <td>{{ $application->name }}</td>
                    <td class="button"><span class="success glyphicon glyphicon-ok"></span></td>
                </tr>

                <tr>
                    <td><b>Basic Description</b></td>
                    <td>{!! nl2br(e($application->description)) !!}</td>
                    <td class="button"><span class="success glyphicon glyphicon-ok"></span></td>
                </tr>
            </tbody>
        </table>

        <h2>Questions About Your Project</h2>

        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Question</th>
                    <th>Answer</th>
                    <th class="button">Required</th>
                </tr>
            </thead>

            <tbody>
                @foreach($questions as $question)
                    <?php

                    $answer = false;
                    $missing = false;

                    // If this question has already been answered, use the update route instead of creating a new answer
                    if(isset($answers[$question->id]))
                    {
                        $answer = $answers[$question->id]->answer;

                        if($question->required && empty($answer))
                        {
                            $missing = true;
                            $answer = "Your answer is missing.";
                        }
                    }

                    ?>

                    <tr class="{{ ($missing) ? 'danger' : '' }}">
                        <td><b>{{ $question->question }}</b></td>
                        <td>{!! nl2br(e($answer)) !!}</td>
                        <td class="button">
                            @if($question->required)
                                <span class="{{ ($missing) ? 'error' : 'success' }} glyphicon glyphicon-ok"></span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <a href="/applications/{{ $application->id }}" class="btn btn-primary">Make Changes</a>
        <button type="submit" class="btn btn-success">Submit Application</button>
    {!! Form::close() !!}
@endsection
