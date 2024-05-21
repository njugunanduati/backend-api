<?php

use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// Controllers
use PragmaRX\Version\Package\Version;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\TasksController;
use App\Http\Controllers\AweberController;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\RPMDialController;
use App\Http\Controllers\CoachingController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\ProspectController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\SessionsController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\S3FileUploadDownloadController;
use App\Http\Controllers\ModuleAnalysisController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PrioritiesController;
use App\Http\Controllers\GetResponseController;
use App\Http\Controllers\ImpCoachingController;
use App\Http\Controllers\ImpSettingsController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\IntegrationsController;
use App\Http\Controllers\LoginTrackerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\FlashCoachingController;
use App\Http\Controllers\ActiveCampaignController;
use App\Http\Controllers\Admin\DesignerController;
use App\Http\Controllers\LeadGenerationController;
use App\Http\Controllers\AssessmentTrailController;
use App\Http\Controllers\Admin\ModuleSetsController;
use App\Http\Controllers\InternalRequestsController;
use App\Http\Controllers\EmailNotificationController;
use App\Http\Controllers\TrainingAnalyticsController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\IncreasePricesExtraController;
use App\Http\Controllers\Admin\ModuleSetModuleController;
use App\Http\Controllers\UserResourceFavoritesController;
use App\Http\Controllers\LicenseeOnboardingController;
use App\Http\Controllers\QuotumController;
use App\Http\Middleware\EnsureTokenBelongsToUser;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/', function () {
    $version = new Version();

    return $version->awesome();
});


// sign up,registration etc request routes...
Route::group(['prefix' => 'auth'], function () {
    Route::post('loginotp', [AuthController::class, 'loginOtp'])->middleware(['throttle:login']);
    Route::post('login', [AuthController::class, 'login'])->middleware(['throttle:login']);
    Route::post('studentlogin', [AuthController::class, 'studentlogin']);
    Route::group(
        ['middleware' => 'auth:api'],
        function () {
            Route::get('user', [AuthController::class, 'user']);
        }
    );
    Route::post('verify', [AuthController::class, 'verifyToken']);

    // recaptcha token
    Route::post('verifyRecaptchaToken', [AuthController::class, 'verifyRecaptchaToken']);

    // OTP
    Route::post('generateOTP', [AuthController::class, 'generateOTP']);
    Route::post('verifyOTP', [AuthController::class, 'verifyOTP']);
});

Route::group(['prefix' => 'auth', 'middleware' => ['auth:sanctum']], function () {
    Route::get('logout', [AuthController::class, 'logout']);
});

// Password reset link request routes...
Route::group(['namespace' => 'Auth', 'middleware' => 'api', 'prefix' => 'password'], function () {
    Route::post('create', [ResetPasswordController::class, 'create'])->name('password.reset.create');
    Route::get('find/{token}', [ResetPasswordController::class, 'find'])->name('password.reset.find');
    Route::post('reset', [ResetPasswordController::class, 'reset'])->name('password.reset');

});

Route::group(['prefix' => 'verify', 'middleware' => ['auth:sanctum', 'cors', 'check.token.expiry']], function () {
    Route::post('/mock', [AuthController::class, 'mockSanctumRequest'])->name('request.handshake');
});

Route::group(['prefix' => 'v1', 'middleware' => ['auth:sanctum', 'cors', 'check.token.expiry']], function () {

    Route::apiResource('roles', RoleController::class);
    // Route::get('rolesset',['as'=>'roles.set.users','uses'=>'RoleController@setUserRoles']);
    Route::apiResource('permissions', PermissionController::class);

    //prospects
    Route::apiResource('prospects', ProspectController::class);
    Route::post('/prospects/search/', [ProspectController::class, 'search'])->name('prospects.search');

    //coaching portal
    Route::apiResource('coachingportal', CoachingController::class);
    Route::post('/coachingportal/settings/', [CoachingController::class, 'updateSettings'])->name('coachingportal.settings');
    Route::post('/coachingportal/appointment/', [CoachingController::class, 'updateAppointment'])->name('coachingportal.appointment');
    Route::post('/coachingportal/appointment/url/', [CoachingController::class, 'updateAppointmentUrl'])->name('coachingportal.appointmenturl');
    Route::post('/coachingportal/appointment/reminder/', [CoachingController::class, 'appointmentReminder'])->name('coachingportal.appointment');
    Route::post('/coachingportal/appointment/delete/', [CoachingController::class, 'appointmentDelete'])->name('coachingportal.appointment.delete');
    Route::post('/coachingportal/details/', [CoachingController::class, 'details'])->name('coachingportal.details');
    Route::post('/coachingportal/notesdetails/', [CoachingController::class, 'notesdetails'])->name('coachingportal.notesdetails');
    Route::post('/coachingportal/alldetails/', [CoachingController::class, 'alldetails'])->name('coachingportal.alldetails');
    Route::post('/coachingportal/notes/', [CoachingController::class, 'getNotes'])->name('coachingportal.notes');
    Route::post('/coachingportal/subscriptions/', [CoachingController::class, 'subscriptions'])->name('coachingportal.subscriptions');
    Route::post('/coachingportal/notes/nextmeetingtime', [CoachingController::class, 'updateNextMeetingTime'])->name('coachingportal.nextmeetingtime');
    // update/ammend previous meeting date
    Route::post('/coachingportal/notes/meetingtime/update/', [CoachingController::class, 'updatePreviousMeetingDate'])->name('coachingportal.previousmeetingdate');
    Route::post('/coachingportal/notes/search/', [CoachingController::class, 'searchNotes'])->name('coachingportal.searchnotes');
    Route::post('/coachingportal/notes/delete/', [CoachingController::class, 'deleteNote'])->name('coachingportal.deletenotes');
    Route::post('/coachingportal/metrics/', [CoachingController::class, 'newUpdateMetrics'])->name('coachingportal.metrics');
    Route::post('/coachingportal/metrics/list/', [CoachingController::class, 'getMetrics'])->name('coachingportal.listmetrics');
    Route::post('/coachingportal/metrics/share/', [CoachingController::class, 'shareMetrics'])->name('coachingportal.sharemetrics');
    Route::post('/coachingportal/history/share/', [CoachingController::class, 'shareHistory'])->name('coachingportal.sharehistory');
    Route::post('/coachingportal/history/download/', [CoachingController::class, 'downloadHistory'])->name('coachingportal.downloadhistory');
    Route::post('/coachingportal/task/', [CoachingController::class, 'newUpdateTask'])->name('coachingportal.task');
    Route::post('/coachingportal/task/delete/', [CoachingController::class, 'destroyTask'])->name('coachingportal.deletetask');
    Route::post('/coachingportal/task/share/', [CoachingController::class, 'shareTask'])->name('coachingportal.sharetask');
    Route::post('/coachingportal/getsettings/', [CoachingController::class, 'getSettings'])->name('coachingportal.getsettings');
    Route::post('/coachingportal/commitment/', [CoachingController::class, 'newUpdateCommitment'])->name('coachingportal.commitment');
    Route::post('/coachingportal/commitment/delete/', [CoachingController::class, 'deleteCommitment'])->name('coachingportal.deletecommitment');
    Route::post('/coachingportal/commitment/status/', [CoachingController::class, 'commitmentStatus'])->name('coachingportal.statuscommitment');
    Route::post('/coachingportal/commitment/download/', [CoachingController::class, 'commitmentDownload'])->name('coachingportal.downloadcommitment');
    Route::post('/coachingportal/updatetimezone/', [CoachingController::class, 'updateTimezone'])->name('coachingportal.updatetimezone');

    Route::post('/coachingportal/report/', [CoachingController::class, 'shareReport'])->name('coachingportal.report');
    Route::post('/coachingportal/resources/', [CoachingController::class, 'shareResources'])->name('coachingportal.resources');
    Route::post('/coachingportal/implementation/', [CoachingController::class, 'saveImplementation'])->name('coachingportal.implementation');
    Route::post('/coachingportal/implementation/show/', [CoachingController::class, 'getImplementation'])->name('coachingportal.getimplementation');
    Route::post('/coachingportal/implementation/archive/', [CoachingController::class, 'archiveImplementation'])->name('coachingportal.archiveimplementation');
    Route::post('/coachingportal/old/meetingnotes/', [CoachingController::class, 'oldMeetingNotes'])->name('coachingportal.oldmeetingnotes');
    Route::post('/coachingportal/old/impcoachingnotes/', [CoachingController::class, 'oldImpCoachingNotes'])->name('coachingportal.oldimpcoachingnotes');
    Route::post('/coachingportal/old/tasks/', [CoachingController::class, 'oldTasks'])->name('coachingportal.oldtasks');
    Route::post('/actionsteps/toggle/', [CoachingController::class, 'toggleActionSteps'])->name('actionsteps.toggle');
    Route::post('/actionsteps/access/', [CoachingController::class, 'actionSteps'])->name('actionsteps');

    //flash coaching
    Route::apiResource('flashcoaching', FlashCoachingController::class);
    Route::post('/flashcoaching/prospects/', [FlashCoachingController::class, 'prospects'])->name('flashcoaching.prospects');
    Route::post('/flashcoaching/newexistingcontact/', [FlashCoachingController::class, 'newexistingcontact'])->name('flashcoaching.newexistingcontact');
    Route::post('/flashcoaching/analysis/', [FlashCoachingController::class, 'analysis'])->name('flashcoaching.analysis');
    Route::post('/flashcoaching/clientanalysis/', [FlashCoachingController::class, 'clientanalysis'])->name('flashcoaching.clientanalysis');
    // Route::post('/flashcoaching/subscriptions/', [FlashCoachingController::class, 'subscriptions'])->name('flashcoaching.subscriptions');
    Route::post('/flashcoaching/progress/', [FlashCoachingController::class, 'progress'])->name('flashcoaching.progress');
    Route::post('/flashcoaching/progress/update/', [FlashCoachingController::class, 'progressUpdate'])->name('flashcoaching.progressupdate');
    Route::post('/flashcoaching/lesson/', [FlashCoachingController::class, 'lesson'])->name('flashcoaching.lesson');
    Route::post('/flashcoaching/toggle/', [FlashCoachingController::class, 'toggle'])->name('flashcoaching.toggle');
    Route::post('/flashcoaching/savenotes/', [FlashCoachingController::class, 'savenotes'])->name('flashcoaching.savenotes');
    Route::post('/flashcoaching/newclient/', [FlashCoachingController::class, 'newclient'])->name('flashcoaching.newclient');
    Route::post('/flashcoaching/removeclient/', [FlashCoachingController::class, 'removeclient'])->name('flashcoaching.removeclient');
    Route::post('/flashcoaching/details/', [FlashCoachingController::class, 'details'])->name('flashcoaching.details');
    Route::post('/flashcoaching/appointment/reminder/', [FlashCoachingController::class, 'appointmentReminder'])->name('flashcoaching.appointmentreminder');
    Route::post('/flashcoaching/appointment/url/', [FlashCoachingController::class, 'updateAppointmentUrl'])->name('flashcoaching.appointmenturl');
    Route::post('/flashcoaching/appointment/update/', [FlashCoachingController::class, 'updateAppointment'])->name('flashcoaching.appointmentupdate');
    Route::post('/flashcoaching/appointment/delete/', [FlashCoachingController::class, 'deleteAppointment'])->name('flashcoaching.appointmentdelete');
    Route::post('/flashcoaching/appointment/new/', [FlashCoachingController::class, 'newAppointment'])->name('flashcoaching.appointmentnew');

    //Events
    Route::apiResource('events', EventsController::class);
    Route::post('/events/analysis/', [EventsController::class, 'analysis'])->name('events.analysis');
    Route::post('/events/toggle/', [EventsController::class, 'toggle'])->name('events.toggle');

    //Tasks
    Route::post('/tasks/coach/', [TasksController::class, 'coachTasks'])->name('tasks.coachtasks');
    Route::post('/tasks/status/', [TasksController::class, 'updateStatus'])->name('tasks.updatestatus');
    Route::post('/tasks/update/', [TasksController::class, 'updateTask'])->name('tasks.updatetask');
    Route::post('/tasks/client/', [TasksController::class, 'getClientCommitments'])->name('tasks.getClientCommitments');
    Route::post('/testimonials/addtestimonial', [TestimonialController::class, 'addTestimonial'])->name('testimonials.addTestimonial');
    Route::post('/testimonials/addreferral', [TestimonialController::class, 'addReferral'])->name('testimonials.addreferral');


    // client portal
    Route::post('/clientportal/notes/', [CoachingController::class, 'getClientMeetingNotes'])->name('clientportal.getClientMeetingNotes');
    Route::post('/clientportal/store/note/', [CoachingController::class, 'storeClientMeetingNote'])->name('clientportal.storeClientMeetingNote');
    Route::post('/clientportal/edit/note/', [CoachingController::class, 'editClientMeetingNote'])->name('clientportal.editClientMeetingNote');
    Route::post('/clientportal/delete/note/', [CoachingController::class, 'deleteClientMeetingNote'])->name('clientportal.deleteClientMeetingNote');
    Route::post('/clientportal/schedule/', [CoachingController::class, 'getClientMeetingSchedule'])->name('clientportal.getClientMeetingSchedule');
    Route::post('/clientportal/change/action/status', [CoachingController::class, 'changeImplementationTaskStatus'])->name('clientportal.changeImplementationTaskStatus');
    Route::post('/clientportal/metrics/list/', [CoachingController::class, 'getMetricsClientPortal'])->name('clientportal.getmetrics');
    Route::post('/clientportal/commitments/list', [CoachingController::class, 'getClientCommitments'])->name('clientportal.getClientCommitments');
    Route::post('/clientportal/metrics/save', [CoachingController::class, 'saveClientMetrics'])->name('clientportal.saveClientMetrics');
    Route::post('/clientportal/metrics/update', [CoachingController::class, 'updateClientMetrics'])->name('clientportal.updateClientMetrics');
    Route::post('/clientportal/metrics/send', [CoachingController::class, 'sendClientMetrics'])->name('clientportal.sendClientMetrics');
    Route::post('/clientportal/implementations/add/', [CoachingController::class, 'addClientImplementation'])->name('clientportal.getClientImplementation');
    Route::post('/clientportal/commitments/update', [TasksController::class, 'updateClientCommitments'])->name('clientportal.updateClientCommitments');
    Route::post('/clientportal/coach/profile/', [CompanyController::class, 'getCoachProfile'])->name('clientportal.getCoachProfile');
    Route::post('/clientportal/team/members/', [TasksController::class, 'getTeamMembers'])->name('clientportal.getTeamMembers');
    Route::post('/clientportal/team/add/', [TasksController::class, 'addTeamMember'])->name('clientportal.addTeamMember');
    Route::post('/clientportal/team/member/assign/', [TasksController::class, 'assignTeamMember'])->name('clientportal.assignTeamMember');
    Route::post('/clientportal/team/task/change/deadline/', [TasksController::class, 'changeDeadline'])->name('clientportal.changeDeadline');
    Route::post('/clientportal/team/task/get/deadline/', [TasksController::class, 'getTaskDeadline'])->name('clientportal.getTaskDeadline');

    //notifications
    Route::apiResource('notifications', NotificationController::class);
    Route::post('/notifications/analysis', [NotificationController::class, 'analysis'])->name('notifications.analysis');
    Route::post('/notifications/toggleanalysis', [NotificationController::class, 'toggleAnalysis'])->name('notifications.toggleAnalysis');
    Route::post('/notifications/toggleall', [NotificationController::class, 'toggleAll'])->name('notifications.toggleAll');
    Route::post('/notifications/delete', [NotificationController::class, 'delete'])->name('notifications.delete');

    //onboarding portal
    Route::apiResource('onboarding', OnboardingController::class);
    Route::post('/onboarding/advisors', [OnboardingController::class, 'advisors'])->name('onboarding.advisors');
    Route::post('/onboarding/advisor', [OnboardingController::class, 'advisor'])->name('onboarding.advisor');
    Route::post('/onboarding/saveadvisor', [OnboardingController::class, 'saveadvisor'])->name('onboarding.saveadvisor');
    Route::post('/onboarding/analytics', [OnboardingController::class, 'analytics'])->name('onboarding.analytics');
    Route::post('/onboarding/analysis/search', [OnboardingController::class, 'analysis'])->name('onboarding.analysis');
    Route::post('/onboarding/analysis/all', [OnboardingController::class, 'analysisAll'])->name('onboarding.analysis.all');
    Route::post('/onboarding/analysis/special/search', [OnboardingController::class, 'specialAnalysis'])->name('onboarding.specialanalysis');
    Route::post('/onboarding/getanalytics', [OnboardingController::class, 'getanalytics'])->name('onboarding.getanalytics');
    Route::post('/onboarding/survey', [OnboardingController::class, 'survey'])->name('onboarding.survey');
    Route::post('/onboarding/manager/search', [OnboardingController::class, 'managersearch'])->name('onboarding.managersearch');
    Route::post('/onboarding/manager/allocate', [OnboardingController::class, 'managerallocate'])->name('onboarding.managerallocate');
    Route::post('/onboarding/note', [OnboardingController::class, 'note'])->name('onboarding.note');
    Route::post('/onboarding/email', [OnboardingController::class, 'email'])->name('onboarding.email');
    Route::post('/onboarding/survey/responses', [OnboardingController::class, 'surveyResponses'])->name('onboarding.surveyresponses');
    Route::get('/onboarding/survey/responses', [OnboardingController::class, 'index'])->name('onboarding.getSurveyResponses');
    Route::post('/onboarding/analysis/licencee/search', [OnboardingController::class, 'analysisByManager'])->name('onboarding.analysisbymanager');
    Route::post('/onboarding/notify', [OnboardingController::class, 'notifyLicensee'])->name('onboarding.notifyLicensee');

    Route::post('/licensee/onboarding/analytics', [LicenseeOnboardingController::class, 'analytics'])->name('licensee.onboarding.analytics');
    Route::post('/licensee/onboarding/getanalytics', [LicenseeOnboardingController::class, 'getanalytics'])->name('licensee.onboarding.getanalytics');
    Route::post('/licensee/onboarding/note', [LicenseeOnboardingController::class, 'note'])->name('licensee.onboarding.note');

    //implementation coaching
    Route::apiResource('impcoaching', ImpCoachingController::class);
    Route::post('/impcoaching/search/', [ImpCoachingController::class, 'search'])->name('impcoaching.search');
    Route::post('/impcoaching/simple/', [ImpCoachingController::class, 'searchByAssessment'])->name('impcoaching.simple');
    Route::post('/impcoaching/actions/', [ImpCoachingController::class, 'actions'])->name('impcoaching.actions');
    Route::post('/impcoaching/remove/', [ImpCoachingController::class, 'remove'])->name('impcoaching.remove');
    Route::post('/impcoaching/steps/', [ImpCoachingController::class, 'steps'])->name('impcoaching.steps');
    Route::post('/impcoaching/task/download/', [ImpCoachingController::class, 'downloadTask'])->name('impcoaching.downloadTask');

    //implementation settings
    Route::apiResource('impsettings', ImpSettingsController::class);
    Route::post('/impsettings/search/', [ImpSettingsController::class, 'search'])->name('impsettings.search');

    //companies
    Route::apiResource('companies', CompanyController::class);
    Route::post('/companies/search/', [CompanyController::class, 'search'])->name('companies.search');
    Route::post('/companies/status/', [CompanyController::class, 'updateStatus'])->name('companies.status');
    Route::post('/companies/user/credentials/', [CompanyController::class, 'sendCredentials'])->name('companies.sendcredentials');
    Route::post('/companies/searchmine/', [CompanyController::class, 'userCompanySearch'])->name('companies.mine');
    Route::get('/companies/user/{id}', [CompanyController::class, 'userCompanies'])->name('companies.user');
    Route::get('/companies/files/{id}', [CompanyController::class, 'companyFiles'])->name('companies.files');
    Route::post('/companies/newfiles/', [CompanyController::class, 'newCompanyFiles'])->name('companies.newfile');
    Route::post('/companies/deletefile/', [CompanyController::class, 'deleteCompanyFile'])->name('companies.deletefile');
    Route::post('/companies/updatefile/', [CompanyController::class, 'updateCompanyFile'])->name('companies.updatefile');
    Route::post('/companies/updatebusiness/', [CompanyController::class, 'updateBusinessType'])->name('companies.updatebusiness');
    Route::post('/companies/image/', [CompanyController::class, 'companyImage'])->name('companies.image');
    /**Client portal company files */
    Route::post('/companies/newcoachfile/', [CompanyController::class, 'newCoachFiles'])->name('companies.newcoachfile');

    //assessments
    Route::apiResource('assessments', AssessmentController::class)->middleware(EnsureTokenBelongsToUser::class);
    Route::post('/assessments/search/', [AssessmentController::class, 'search'])->name('assessments.search');
    Route::get('/assessments/user/{id}', [AssessmentController::class, 'userAssessments'])->name('assesssments.user');
    Route::get('/assessments/company/{id}', [AssessmentController::class, 'companyAssessments'])->name('assesssments.company');
    Route::get('/assessments/user/simple/{id}', [AssessmentController::class, 'userSimpleAssessments'])->name('assesssments.simple');

    Route::post('/assessments/disable/settings', [AssessmentController::class, 'disableAssessmentReminders'])->name('assesssments.disableAssessmentReminders');
    /**Client portal assessments **/
    Route::post('/assessments/company/simple/', [AssessmentController::class, 'clientSimpleAssessments'])->name('assesssments.clientSimple');

    Route::post('/assessments/analysis/', [AssessmentController::class, 'assessmentAnalysis'])->name('assesssments.analysis');
    Route::post('/assessments/analysis/{assessment_id}', [AssessmentController::class, 'assessmentSingleAnalysis'])->name('assesssments.analyze.one');
    Route::post('/assessments/questions/get/{assessment_id}', [AssessmentController::class, 'loadQuestion']);
    Route::post('/assessments/comments/save/{assessment_id}', [AssessmentController::class, 'saveComment']);
    Route::post('/assessments/otherindustry/{assessment_id}', [AssessmentController::class, 'updateOtherIndustry']);
    Route::post('/assessments/cost/save/{assessment_id}', [AssessmentController::class, 'saveCostOfCoaching']);
    Route::post('/assessments/agreements/save/{assessment_id}', [AssessmentController::class, 'saveAgreements']);
    Route::post('/assessments/revenue/save/{assessment_id}', [AssessmentController::class, 'saveRevenueShare']);
    Route::post('/assessments/implementation/save/{assessment_id}', [AssessmentController::class, 'saveImplementationDate']);
    Route::post('/assessments/toggleplanningmeetings/save/{assessment_id}', [AssessmentController::class, 'togglePlanningMeetings']);
    Route::post('/assessments/togglereviewmeetings/save/{assessment_id}', [AssessmentController::class, 'toggleReviewMeetings']);
    Route::post('/assessments/planningmeetings/save/{assessment_id}', [AssessmentController::class, 'savePlanningMeetings']);
    Route::post('/assessments/responses/save/{assessment_id}', [AssessmentController::class, 'saveBulkResponse']);
    Route::post('/assessments/responses/single/{assessment_id}', [AssessmentController::class, 'saveSingleResponse']);
    Route::post('/assessments/percent/', [AssessmentController::class, 'addPercent']);

    // Currency
    Route::apiResource('currencies', CurrencyController::class);

    // Assessment Trails
    Route::apiResource('trails', AssessmentTrailController::class);

    //price extras
    Route::apiResource('priceextras', IncreasePricesExtraController::class);

    Route::apiResource('sessions', SessionsController::class);
    Route::get('sessions/assesssment/{id}', [SessionsController::class, 'assessment'])->name('session.assessment');
    Route::post('/sessions/mail', [SessionsController::class, 'notesNotify']);

    Route::post('/reports/mail', [ReportsController::class, 'sendMail']);
    //Users
    Route::apiResource('users', UserController::class);
    Route::post('/students/update/{id}', [UserController::class, 'updateStudent'])->name('student.update');
    /**Client portal update for new columns **/
    Route::post('/students/updatetwo/{id}', [UserController::class, 'updateStudentProfile'])->name('student_two.update');
    /**Client portal update profile picture **/
    Route::post('/student/profilephoto/update', [UserController::class, 'updateprofilephoto'])->name('student.updateprofilephoto');
    Route::post('/users/tour/{id}', [UserController::class, 'toggleShowTour'])->name('users.tour');
    Route::post('/users/prospectsnotify/{id}', [UserController::class, 'toggleProspectsNotify'])->name('users.prospectsnotify');
    Route::post('/users/search/', [UserController::class, 'search'])->name('users.search');
    Route::post('/users/updateprofile/', [UserController::class, 'updateUserProfile'])->name('users.updateprofile');
    Route::post('/users/coach/search/', [UserController::class, 'searchCoachUsers'])->name('users.search-coach');
    Route::post('/users/download/', [UserController::class, 'download'])->name('users.download');
    Route::post('/users/company/', [UserController::class, 'companyUsers'])->name('users.company');
    Route::post('/users/notify/', [UserController::class, 'notifyAdmin'])->name('users.notify');
    Route::post('/users/analysis/', [UserController::class, 'analysisEmail']);
    Route::post('/users/studentemail/', [UserController::class, 'studentEmail']);
    Route::post('/users/studentpaid/', [UserController::class, 'studentHasPaid']);
    Route::post('/users/newstudentnotice/', [UserController::class, 'newStudentNotification']);
    Route::post('/users/newstudentuser/', [UserController::class, 'newStudentUser']);
    Route::post('/users/getgroup/', [UserController::class, 'getGroupDetails']);
    Route::post('/users/savegroup', [UserController::class, 'saveNewGroup'])->name('users.savegroup');
    Route::post('/users/updategroup/', [UserController::class, 'updateGroupDetails']);
    Route::post('/users/addmoduleset/', [UserController::class, 'addModulesetAllUsers']);
    Route::post('/users/multirolechange/', [UserController::class, 'updateMultiUsersRoles']);
    Route::post('/users/edit_assessment_permissions/{assessment_id}', [UserController::class, 'editAssessmentPermissions']);
    Route::post('/users/detach_user_assessment/{assessment_id}', [UserController::class, 'detachUserAssessment']);
    Route::post('/users/detach_user_moduleset/{moduleset_id}', [UserController::class, 'detachUserModuleset']);
    Route::post('/users/training/{user_id}', [UserController::class, 'changeTrainings']);
    Route::post('/users/onboarding/', [UserController::class, 'changeOnboarding']);
    Route::get('/users/user_assessments/{user_id}', [UserController::class, 'assessmentsByUserId']);
    Route::post('/users/add_module_sets', [UserController::class, 'addModuleSet']);
    Route::post('/users/email', [UserController::class, 'sendUserEmail']);
    Route::get('/users/company-permissions/{user_id}/{company_id}', [UserController::class, 'toggleCompanyPermissions']);
    Route::post('/users/del-group-member/', [UserController::class, 'delGroupMember']);
    Route::post('/users/add/custom-group', [UserController::class, 'addCustomGroup'])->name('users.custom-group');
    Route::post('/users/income/', [UserController::class, 'updateIncome'])->name('users.income');
    Route::post('/users/onboarding/edit/{id}', [UserController::class, 'updateOnboarding'])->name('users.onboarding.edit');
    Route::post('/users/licensee/', [UserController::class, 'changeLicensee']);
    Route::post('/users/highrise/', [UserController::class, 'getHighRiseInfo']);
    // user logs
    Route::post('/users/account/logs/', [UserController::class, 'getUserAccountLogs']);


    // Members controller
    Route::post('lesson-members', [MemberController::class, 'getLessonUsers'])->name('lesson.users');
    Route::apiResource('member-group-lessons', MemberController::class);
    Route::apiResource('members', MemberController::class);
    Route::post('members/pause/sessions', [MemberController::class, 'pauseSessions'])->name('members.pause');
    Route::post('members/resume/sessions', [MemberController::class, 'resumeSessions'])->name('members.unpause');

    // Update custom group lessons to add the lesson order
    Route::post('members/updategclm', [MemberController::class, 'updategclm'])->name('members.updategclm');
    Route::post('members/updatecgl', [MemberController::class, 'updatecgl'])->name('members.updatecgl');
    Route::post('members/updatemgls', [MemberController::class, 'updatemgls'])->name('members.updatemgls');

    //Group coaching groups
    Route::apiResource('groups', GroupController::class);
    Route::get('groups/user/{id}', [GroupController::class, 'userGroups'])->name('groups.user');
    Route::get('groups/usergroups/{id}', [GroupController::class, 'getUserGroups'])->name('usergroups.user');
    Route::post('groups/usergroup/', [GroupController::class, 'getUserGroup'])->name('usergroup.get');
    Route::post('groups/templates', [GroupController::class, 'getGroupTemplates'])->name('groups.templates');
    Route::post('groups/templates/create', [GroupController::class, 'createUserGroupTemplate'])->name('templates.create');
    Route::post('groups/editcustomgroup', [GroupController::class, 'editCustomGroup'])->name('custtomgroup.edit');

    //Questions
    Route::post('questions/get_by_module', [QuestionController::class, 'getModuleQuestions'])->name('questions.moduleQuestions');
    // ->middleware(['throttle:getByModule']);

    //Priorities
    Route::apiResource('priorities', PrioritiesController::class);
    Route::get('priorities/implementation/{id}', [PrioritiesController::class, 'prioritiesImplementation'])->name('priorities.implementation');
    Route::get('priorities/assessment/{id}', [PrioritiesController::class, 'getByAssessmentId'])->name('priorities.assessment');

    Route::apiResource('tracker', LoginTrackerController::class);
    Route::post('/tracker/monthly/', [LoginTrackerController::class, 'monthly']);
    Route::apiResource('tickets', TicketController::class);
    Route::apiResource('rpmdial', RPMDialController::class);
    Route::post('meta', [DesignerController::class, 'module_meta_data']);

    //Integrations
    Route::apiResource('integrations', IntegrationsController::class);
    Route::post('integrations/list', [IntegrationsController::class, 'userIntegrations'])->name('user.integrations');
    Route::post('integrations/remove', [IntegrationsController::class, 'removeIntegrations'])->name('remove.integrations');
    Route::post('integrations/attachments', [IntegrationsController::class, 'getAttachments'])->name('attachments.integrations');
    Route::post('integrations/attachments/save', [IntegrationsController::class, 'saveAttachments'])->name('attachments.save');

    //Aweber Integrations
    Route::apiResource('integrations/aweber', AweberController::class);
    Route::post('integrations/aweber/request/lists', [AweberController::class, 'getLists'])->name('aweber.lists');

    Route::post('integrations/aweber/request/subscribe', [AweberController::class, 'subscribeToListRequest'])->name('aweber.users.subscribe');
    Route::post('integrations/aweber/request/broadcast', [AweberController::class, 'createBroadcastRequest'])->name('aweber.broadcasts');
    // Route::post('integrations/aweber/request/broadcast/schedule',['as'=>'aweber.broadcasts.schedule','uses'=>'AweberController@scheduleBroadcastRequest']);

    //GetResponse Integrations
    Route::apiResource('integrations/getresponse', GetResponseController::class);
    Route::post('integrations/getresponse/get_campaigns/', [GetResponseController::class, 'getList']);
    Route::post('integrations/getresponse/get_time', [GetResponseController::class, 'getExpiresIn']); //for converting expires_in to timestamp
    Route::post('integrations/getresponse/add_contact', [GetResponseController::class, 'addContact']);
    Route::post('integrations/getresponse/create_newsletter', [GetResponseController::class, 'createNewsletter']);
    Route::post('integrations/getresponse/token', [GetResponseController::class, 'getToken']);


    //Training Analytics
    Route::apiResource('training-analytics', TrainingAnalyticsController::class);
    Route::post('training-analytics/search/', [TrainingAnalyticsController::class, 'search'])->name('training-analytics.search');
    Route::post('training-analytics/user/', [TrainingAnalyticsController::class, 'searchByUser'])->name('training-analytics.user');

    // Favorites resource
    Route::get('favorites/user/{user_id}', [UserResourceFavoritesController::class, 'getFavorites']);
    Route::post('favorites/user/resource', [UserResourceFavoritesController::class, 'addFavorites']);
    Route::post('favorites/user/resource/remove', [UserResourceFavoritesController::class, 'removeFavorite']);

    // add active campaign credentials
    Route::post('activecampaign/credentials/add', [ActiveCampaignController::class, 'addCredentials']);
    // update active campaign credentials
    Route::post('activecampaign/credentials/update', [ActiveCampaignController::class, 'updateCredentials']);
    // get active campaign credentials
    Route::get('activecampaign/credentials/get/{user_id}', [ActiveCampaignController::class, 'getCredentials']);
    // delete active campaign credentials
    Route::get('activecampaign/credentials/delete/{user_id}', [ActiveCampaignController::class, 'deleteCredentials']);
    // active campaign apis
    Route::post('activecampaign/create_campaign', [ActiveCampaignController::class, 'createCampaign']);
    // active campaign view list
    Route::get('activecampaign/view_list/{user_id}', [ActiveCampaignController::class, 'viewLists']);
    // active campaign create contact
    Route::post('activecampaign/add_contact', [ActiveCampaignController::class, 'addContact']);
    // active campaign create message
    Route::post('activecampaign/create_message', [ActiveCampaignController::class, 'createMessage']);
    // active campaign create campaign
    Route::post('activecampaign/create_campaign', [ActiveCampaignController::class, 'createCampaign']);

    // S3 file upload with presigned url
    Route::post('s3/fileupload', [S3FileUploadDownloadController::class, 'fileUpload']);
    Route::post('s3/showfile', [S3FileUploadDownloadController::class, 'getFile']);

    //Group coaching lessons
    Route::apiResource('lessons', LessonController::class);

    Route::post('lessons/member-group-lesson', [LessonController::class, 'addmemberGroupLesson'])->name('lessons.memberGroupLesson');
    Route::post('lessons/link', [LessonController::class, 'linkLessonToGroup'])->name('lessons.links');
    Route::post('lessons/custom-group-lesson', [LessonController::class, 'addCustomGroupLesson'])->name('lessons.addCustomGroupLesson');
    Route::post('lessons/resource/delete', [LessonController::class, 'deleteSingleResource'])->name('lessons.resourcedelete');
    Route::post('lessons/delete', [LessonController::class, 'deleteLesson'])->name('lessons.delete');
    Route::post('lessons/delink', [LessonController::class, 'delinkLesson'])->name('lessons.delink');
    Route::post('lessons/multilink', [LessonController::class, 'multiLessonLink'])->name('lessons.multilink');
    Route::post('template-lessons', [LessonController::class, 'getLessonCount'])->name('template.lessons');
    Route::post('lessons/reorder', [LessonController::class, 'updateLessonOrder'])->name('lessons.reorder');

    Route::post('lessons/recordings', [LessonController::class, 'addMeetingRecording'])->name('lessons.recordings');
    Route::post('lessons/recording', [LessonController::class, 'getMeetingRecording'])->name('lessons.recording');
    Route::post('lessons/recording/delete', [LessonController::class, 'deleteMeetingRecording'])->name('lessons.recording.delete');

    //Group coaching resources
    Route::apiResource('resources', ResourceController::class);

    //Group coaching email templates
    Route::apiResource('email-templates', EmailNotificationController::class);

    Route::post('accessAnalysis', [ModuleAnalysisController::class, 'moduleAccessAnalysis'])->name('module-analysis.accessAnalysis');
    Route::post('impactAnalysis', [ModuleAnalysisController::class, 'impactAnalysis'])->name('module-analysis.impactAnalysis');
    Route::post('moduleResponses', [ModuleAnalysisController::class, 'getResponsesByModule'])->name('module-analysis.moduleResponses');
    Route::post('assessment-stats', [ModuleAnalysisController::class, 'assesmentsStats'])->name('module-analysis.assesmentsStats');

     // ai
    Route::get('ai/searchSuggestions', [SuggestionController::class, 'searchSuggestions'])->name('suggestions.search');
    Route::post('ai/liveSearch', [SuggestionController::class, 'liveSearch'])->name('suggestions.liveSearch');
    Route::post('ai/upload', [SuggestionController::class, 'uploadSuggestions'])->name('suggestions.uploadSuggestions');
    Route::get('ai/paths', [SuggestionController::class, 'getPaths'])->name('suggestions.getPaths');
    Route::get('ai/businesses', [SuggestionController::class, 'getBusinesses'])->name('suggestions.getBusinesses');
    Route::get('ai/questions', [SuggestionController::class, 'getQuestions'])->name('suggestions.getQuestions');
    Route::get('ai/listquestions', [SuggestionController::class, 'listquestions'])->name('suggestions.listquestions');
    Route::get('ai/responses', [SuggestionController::class, 'getResponses'])->name('suggestions.getResponses');
    Route::post('ai/login', [SuggestionController::class, 'authenticateInterns'])->name('suggestions.authenticateInterns');
    Route::post('ai/history/howtos', [SuggestionController::class, 'addHowToHistory'])->name('suggestions.addHowToHistory');
    Route::get('ai/history/howtos', [SuggestionController::class, 'getHowToHistory'])->name('suggestions.getHowToHistory');
    Route::post('ai/history/responses', [SuggestionController::class, 'addResponseHistory'])->name('suggestions.addResponseHistory');
    Route::get('ai/history/responses', [SuggestionController::class, 'getResponseHistory'])->name('suggestions.getResponseHistory');
    Route::apiResource('ai/suggestions', SuggestionController::class);
    // Lead Generation
    Route::get('lead-generation/advisors', [LeadGenerationController::class, 'getAdvisors'])->name('leadGeneration.getAdvisors');
    Route::post('lead-generation/advisor', [LeadGenerationController::class, 'saveAdvisor'])->name('leadGeneration.saveAdvisor');
    Route::get('lead-generation/advisor/{user_id}', [LeadGenerationController::class, 'getAdvisor'])->name('leadGeneration.getAdvisor');
    Route::get('lead-generation/analysis/all', [LeadGenerationController::class, 'analysisAll'])->name('leadGeneration.analysisAll');  
    Route::post('lead-generation/analysis/special/search', [LeadGenerationController::class, 'specialAnalysis'])->name('leadGeneration.specialanalysis');
    Route::post('lead-generation/analysis/search', [LeadGenerationController::class, 'analysis'])->name('leadGeneration.analysis');
    Route::get('lead-generation/steps', [LeadGenerationController::class, 'getSteps'])->name('leadGeneration.getSteps');
    Route::post('lead-generation/progress', [LeadGenerationController::class, 'saveProgress'])->name('leadGeneration.saveProgress');
    Route::get('lead-generation/progress', [LeadGenerationController::class, 'getProgress'])->name('leadGeneration.getProgress');
    Route::get('lead-generation/user-scripts/{id}', [LeadGenerationController::class, 'getUserScripts'])->name('leadGeneration.getUserScripts');
    Route::post('lead-generation/user-scripts', [LeadGenerationController::class, 'saveUserScripts'])->name('leadGeneration.saveUserScripts');
    Route::post('lead-generation/saveVideoProgress/', [LeadGenerationController::class, 'saveVideoProgress'])->name('leadGeneration.saveVideoProgress');
    Route::get('lead-generation/videoAnalysis/{user_id}', [LeadGenerationController::class, 'videoAnalysis'])->name('leadGeneration.videoAnalysis');
    Route::post('lead-generation/lesson/', [LeadGenerationController::class, 'lesson'])->name('leadGeneration.lesson');
    Route::post('lead-generation/script/download/', [LeadGenerationController::class, 'downloadScript'])->name('leadGeneration.downloadScript');
    Route::apiResource('lead-generation', LeadGenerationController::class);

    //Quotum
    Route::apiResource('quotum', QuotumController::class);
    Route::post('quotum/save/questionnaire/', [QuotumController::class, 'saveQuestionnaire'])->name('quotum.save_questionnaire');
    Route::post('quotum/get/questionnaire/', [QuotumController::class, 'getQuestionnaire'])->name('quotum.get_questionnaire');
    Route::post('quotum/save/expertise/', [QuotumController::class, 'expertise'])->name('quotum.expertise');
    Route::post('quotum/get/content/', [QuotumController::class, 'getQuotum'])->name('quotum.getQuotum');
    Route::post('quotum/get/email/content/', [QuotumController::class, 'getEmailQuotum'])->name('quotum.getEmailQuotum');
    Route::post('quotum/send/email/', [QuotumController::class, 'sendQuotumEmail'])->name('quotum.sendQuotumEmail');
    Route::post('quotum/get/levelone/', [QuotumController::class, 'getLevelOne'])->name('quotum.getLevelOne');
    Route::post('quotum/get/commitments/', [QuotumController::class, 'getCommitments'])->name('quotum.getCommitments');
    Route::post('quotum/manage/commitments/', [QuotumController::class, 'manageCommitments'])->name('quotum.manageCommitments');
    //Simulator
    Route::post('simulation/saveUrl/{user_id}', [SimulationController::class, 'saveUrl']);
    Route::post('simulation/fetchByUserId',  [SimulationController::class, 'fetchByUserId']);
    Route::post('simulation/fetchSimulationById',  [SimulationController::class, 'fetchSimulationById']);

});

// Admin Namespace
Route::group(['prefix' => 'v1'], function () {
    Route::apiResource('modulesets', ModuleSetsController::class);
    Route::get('modulesets/user/{id}', [ModuleSetsController::class, 'userModuleSets'])->name('modulesets.user');
    Route::apiResource('modules', ModuleSetModuleController::class);
    Route::get('modules/get_by_order/{module_set_id}', [ModuleSetModuleController::class, 'getByOrder'])->name('modulesets.order');
});

// Free Namespace Registration routes...
Route::group(['prefix' => 'free'], function () {
    Route::post('register', [UserController::class, 'freeRegister'])->name('free.user.registration');
});

// Local endpoints
Route::group(['prefix' => 'internal'], function () {
    Route::post('users', [UserController::class, 'coachList'])->name('coaches.list');
});

Route::group(['prefix' => 'internal', 'middleware' => ['basicAuth', 'cors']], function () {

    Route::post('users/salesform/add/', [InternalRequestsController::class, 'addSalesFormMember'])->name('internal-requests.add-salesform-member');
});



// Rate limiting to 60 requests per minute
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/simulation/{uniqueToken}', [SimulationController::class, 'accessPublicPage']);
    Route::post('/simulation/report', [SimulationController::class, 'accessReportPage']);
    // Simulator and Sim old
    Route::post('/simulation/get/old/', [SimulationController::class, 'getOld']);
    Route::post('simulation/add/old', [SimulationController::class, 'postOld']);
    Route::post('simulation/meet/old', [SimulationController::class, 'request_meet_old']);

    Route::get('/simulation/{uniqueToken}', [SimulationController::class, 'accessPublicPage']);
    // Simulator
    Route::post('simulation/add', [SimulationController::class, 'post']);
    Route::get('simulation/get', [SimulationController::class, 'get']);
    Route::post('simulation/meet', [SimulationController::class, 'request_meet']);
    Route::post('simulation/update/uuid', [SimulationController::class, 'updateUUIDs']);
    // Currency list for Simulator
    Route::get('simulation/currencies', [CurrencyController::class, 'index']);
    Route::post('lookup', [SimulationController::class, 'loginLookpup']);
    // verify recaptcha
    Route::post('simulation/verifyRecaptchaToken', [SimulationController::class, 'verifyRecaptchaToken']);
    // test ip address 
    Route::post('simulation/getip', [SimulationController::class, 'getIp']);
});

// Query update endpoints
Route::group(['prefix' => 'v1', 'middleware' => ['isAuthorized', 'cors']], function () {
    Route::get('update/add/traininguser', [UpdateController::class, 'create'])->name('update.traininguser');
    Route::get('update/add/companyuser', [UpdateController::class, 'createCompanyUser'])->name('create.companyuser');
    Route::get('update/add/usercompanyrelation', [UpdateController::class, 'createUserCompanyRelation'])->name('create.usercompanyrelation');
    Route::get('update/add/trainingaccess', [UpdateController::class, 'createTrainingAccess'])->name('create.trainingaccess');
    Route::get('update/add/profitmetrics', [UpdateController::class, 'createProfitMetrics'])->name('create.profitmetrics');
});

