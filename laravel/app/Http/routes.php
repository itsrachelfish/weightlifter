<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

// Routes available to all users
Route::group(['middleware' => ['web']], function()
{
    // Basic pages
    Route::get('/', 'PageController@home');
    Route::get('/about', 'PageController@view');

    // User authentication routes
    Route::get('/register', 'PageController@view');
    Route::get('/login', 'PageController@view');
    Route::get('/logout', 'UserController@logout');

    Route::post('/register', 'UserController@create');
    Route::post('/login', 'UserController@login');
});

// Routes available to logged in users
Route::group(['middleware' => ['auth']], function()
{
    // Creating and viewing grant applications
    Route::get('/applications', 'ApplicationController@listApplications');
    Route::post('/applications', 'ApplicationController@createApplication');
    Route::get('/applications/create', 'ApplicationController@createApplicationForm');
    Route::get('/applications/{application}', 'ApplicationController@viewApplication');
    Route::post('/applications/{application}', 'ApplicationController@updateApplication');
    Route::get('/applications/{application}/review', 'ApplicationController@reviewApplication');
    Route::post('/applications/{application}/submit', 'ApplicationController@submitApplication');

    // Answering questions
    Route::post('/answers', 'AnswerController@createAnswer');
    Route::post('/answers/{answer}', 'AnswerController@updateAnswer');

    // Handling documents
    Route::post('/documents/{application}/add', 'DocumentController@addDocument');
    Route::get('/documents/{document}/delete', 'DocumentController@deleteDocument');

    // Profile (UserData)
    Route::get('/users/profile', 'UserController@editSelf');
    Route::post('/users/profile', 'UserController@updateSelf');

    // Responding to feedback
    Route::post('/feedback/{feedback}', 'FeedbackController@updateFeedback');
});

// Routes available to both admins and judges
Route::group(['middleware' => ['auth', 'role:admin|judge|observer']], function()
{
    // Viewing questions
    Route::get('/questions', 'QuestionController@listQuestions');

    // Viewing criteria
    Route::get('/criteria', 'CriteriaController@listCriteria');

    // Viewing users
    Route::get('/users', 'UserController@listUsers');
    Route::get('/users/{user}', 'UserController@viewUser');
});

// Routes only available to admins
Route::group(['middleware' => ['auth', 'role:admin']], function()
{
    // Creating and modifying questions
    Route::post('/questions', 'QuestionController@createQuestion');
    Route::get('/questions/create', 'QuestionController@createQuestionForm');
    Route::get('/questions/{question}', 'QuestionController@editQuestionForm');
    Route::post('/questions/{question}', 'QuestionController@editQuestion');
    Route::get('/questions/{question}/delete', 'QuestionController@deleteQuestion');

    // Creating and modifying criteria
    Route::post('/criteria', 'CriteriaController@createCriteria');
    Route::get('/criteria/create', 'CriteriaController@createCriteriaForm');
    Route::get('/criteria/{criteria}', 'CriteriaController@editCriteriaForm');
    Route::post('/criteria/{criteria}', 'CriteriaController@editCriteria');
    Route::get('/criteria/{criteria}/delete', 'CriteriaController@deleteCriteria');

    //Modifying Users
    Route::get('/users/{user}/edit', 'UserController@editUser');
    Route::post('/users/{user}/edit', 'UserController@updateUser');

});

// Routes only available to judges
Route::group(['middleware' => ['auth', 'role:judge']], function()
{
    // Scoring criteria
    Route::post('/score', 'ScoreController@scoreCriteria');

    // Submitting finalized scores
    Route::post('/applications/{application}/judge', 'ApplicationController@judgeApplication');

    // Requesting feedback
    Route::get('/applications/{application}/feedback/{question?}', 'FeedbackController@createFeedbackForm');
    Route::post('/feedback', 'FeedbackController@createFeedback');
});
