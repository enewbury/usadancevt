<?php
// Routes

use EricNewbury\DanceVT\Controllers\AdminPanelController;
use EricNewbury\DanceVT\Controllers\AjaxController;
use EricNewbury\DanceVT\Controllers\InstructorPanelController;
use EricNewbury\DanceVT\Controllers\OrganizationPanelController;
use EricNewbury\DanceVT\Controllers\RegistrationController;
use EricNewbury\DanceVT\Controllers\UserAccountController;
use EricNewbury\DanceVT\Controllers\WebsiteController;
use EricNewbury\DanceVT\Util\Middleware\HtmlAuthorizationMiddleware;
use EricNewbury\DanceVT\Util\Middleware\JsonAuthorizationMiddleware;

$app->group('/account', function() use ($app){
    $app->get('/create', RegistrationController::class . ':renderCreateAccountPage')->setName('createAccount');
    $app->post('/create', RegistrationController::class . ':createAccount');
    $app->get('/thank-you', RegistrationController::class. ':thankYou')->setName('thankYou');
    $app->get('/verify/resend', RegistrationController::class.':resendVerification')->setName('resendVerification');
    $app->get('/verify/{userId}', RegistrationController::class.':verify')->setName('verifyAccount');

    $app->get('/forgot-password', UserAccountController::class.':loadForgotPassword')->setName('forgotPassword');
    $app->post('/forgot-password', UserAccountController::class.':forgotPassword');
    $app->get('/reset-password/{userId}', UserAccountController::class.':loadResetPassword')->setName('resetPassword');
    $app->post('/reset-password/{userId}', UserAccountController::class.':resetPassword');

    $app->get('/delete', UserAccountController::class.':deleteAccount')->add(HtmlAuthorizationMiddleware::class.':assertLoggedIn')->setName('deleteAccount');

    $app->group('', function() use($app){
        $app->get('/site-admin/request', UserAccountController::class.':requestSiteAdminPermission');
        $app->get('/site-admin/deactivate', UserAccountController::class.':deactivateSiteAdminPermission');
        $app->get('/instructor-admin/activate', UserAccountController::class.':activateInstructorAdminAccount');
        $app->get('/instructor-admin/deactivate', UserAccountController::class.':deactivateInstructorAdminAccount');
        $app->get('/organization-admin/activate', UserAccountController::class.':activateOrganizationAdminAccount');
        $app->get('/organization-admin/deactivate', UserAccountController::class.':deactivateOrganizationAdminAccount');
    })->add(HtmlAuthorizationMiddleware::class.':assertLoggedIn');

});

$app->get('/login', UserAccountController::class.':loadLogin')->setName('login');
$app->post('/login', UserAccountController::class.':login');
$app->get('/logout', UserAccountController::class.':logout')->setName('logout');

$app->post('/subscribe', RegistrationController::class.':subscribe');
$app->get('/unsubscribe', RegistrationController::class.':loadUnsubscribe');
$app->post('/unsubscribe', RegistrationController::class.':unsubscribe');

//ADMIN
$app->group('/admin-panel', function () use ($app) {
    $app->get('', AdminPanelController::class.':root')->setName('adminPanelHome');
    $app->any('/accounts', AdminPanelController::class.':accounts')->setName('adminPanelAccounts');
    $app->get('/organization', AdminPanelController::class.':loadOrganizations')->setName('adminPanelOrganizations');
    $app->get('/organization/{id}', AdminPanelController::class.':loadOrganizationProfile')->setName('adminPanelOrganization');
    $app->post('/organization/{id}', AdminPanelController::class.':updateOrganization');
    $app->get('/instructor', AdminPanelController::class.':loadInstructors');
    $app->get('/instructor/{id}', AdminPanelController::class.':loadInstructorProfile')->setName('adminPanelInstructor');
    $app->post('/instructor/{id}', AdminPanelController::class.':updateInstructorProfile');
    $app->get('/event', AdminPanelController::class.':loadEvents')->setName('adminEventList');
    $app->get('/event/{eventId}[/date/{date}]', AdminPanelController::class.':loadEvent')->setName('adminPanelEvent');
    $app->post('/event/{eventId}[/date/{date}]',AdminPanelController::class.':updateEvent');
    $app->get('/page', AdminPanelController::class.':loadPages');
    $app->post('/page', AdminPanelController::class.':createPage');
    $app->get('/page/{pageId}', AdminPanelController::class.':loadPage')->setName('adminPanelPage');
    $app->post('/page/{pageId}', AdminPanelController::class.':updatePage');
    $app->get('/global-settings', AdminPanelController::class.':loadGlobalSettingsPage')->setName('adminPanelGlobals');
    $app->post('/global-settings', AdminPanelController::class.':updateGlobals');
    $app->get('/email-tool', AdminPanelController::class.':loadEmailToolPage')->setName('emailTool');
    $app->post('/email-tool', AdminPanelController::class.':sendNewsletter');
})->add(HtmlAuthorizationMiddleware::class.':assertAdmin');

//ORGANIZATION
$app->group('/organization-panel', function () use ($app) {
    $app->get('/all', OrganizationPanelController::class.':redirectToAll');
    $app->get('', OrganizationPanelController::class.':loadAllForUser')->setName('organizationPanelHome');
    $app->post('', OrganizationPanelController::class.':requestNewOrganizationForUser');

    $app->group('', function() use ($app){
        $app->get('/{id}/profile', OrganizationPanelController::class.':loadProfile');
        $app->post('/{id}/profile', OrganizationPanelController::class.':updateProfile');
        $app->get('/{id}/event', OrganizationPanelController::class.':loadEvents')->setName('organizationEventList');
    })->add(HtmlAuthorizationMiddleware::class.':assertValidOrganizationAdmin');

    $app->group('', function() use($app){
        $app->get('/{id}/event/{eventId}[/date/{date}]', OrganizationPanelController::class.':loadEvent')->setName('organizationPanelEvent');
        $app->post('/{id}/event/{eventId}[/date/{date}]', OrganizationPanelController::class.':updateEvent');
    })->add(HtmlAuthorizationMiddleware::class.':assertValidOrganizationForEvent');
})->add(HtmlAuthorizationMiddleware::class.':assertOrganizationAdmin');

//INSTRUCTOR
$app->group('/instructor-panel', function () use ($app) {
    $app->get('/all', InstructorPanelController::class.':redirectToAll');
    $app->get('', InstructorPanelController::class.':loadAllForUser')->setName('instructorPanelHome');
    $app->post('', InstructorPanelController::class.':requestNewInstructorForUser');

    $app->group('', function() use ($app){
        $app->get('/{id}/profile', InstructorPanelController::class.':loadProfile');
        $app->post('/{id}/profile', InstructorPanelController::class.':updateProfile');
        $app->get('/{id}/event', InstructorPanelController::class.':loadEvents')->setName('instructorEventList');
    })->add(HtmlAuthorizationMiddleware::class.':assertValidInstructorAdmin');
    
    $app->group('', function() use($app){
        $app->get('/{id}/event/{eventId}[/date/{date}]', InstructorPanelController::class.':loadEvent')->setName('instructorPanelEvent');
        $app->post('/{id}/event/{eventId}[/date/{date}]', InstructorPanelController::class.':updateEvent');
    })->add(HtmlAuthorizationMiddleware::class.':assertValidInstructorForEvent');
})->add(HtmlAuthorizationMiddleware::class.':assertInstructorAdmin');

//AJAX
$app->group('/ajax', function() use($app){
    $app->get('/event/next-instances', AjaxController::class. ':loadEventsSingleInstance');

    $app->post('/organization-activation', AjaxController::class.':updateOrganizationActivation')
    ->add(JsonAuthorizationMiddleware::class.':assertAdminOrValidOrganizationAdmin');

    $app->post('/delete-organization', AjaxController::class.':deleteOrganization')
    ->add(JsonAuthorizationMiddleware::class.':assertAdminOrValidOrganizationAdmin');

    $app->post('/instructor-activation', AjaxController::class.':updateInstructorActivation')
    ->add(JsonAuthorizationMiddleware::class.':assertAdminOrValidInstructorAdmin');

    $app->post('/delete-instructor', AjaxController::class.':deleteInstructor')
    ->add(JsonAuthorizationMiddleware::class.':assertAdminOrValidInstructorAdmin');

    $app->post('/event-activation', AjaxController::class.':updateEventActivation')
        ->add(JsonAuthorizationMiddleware::class.':assertAdminOrValidEventAdmin');

    $app->post('/delete-event', AjaxController::class.':deleteEvent')
        ->add(JsonAuthorizationMiddleware::class.':assertAdminOrValidEventAdmin');

    $app->post('/remove-organization-access', AjaxController::class.':removeOrganizationAccessFromUser')
    ->add(JsonAuthorizationMiddleware::class.':assertOrganizationAdminForOrganization');

    $app->post('/remove-instructor-access', AjaxController::class.':removeInstructorAccessFromUser')
    ->add(JsonAuthorizationMiddleware::class.':assertInstructorAdminForInstructor');

    $app->group('', function() use($app){
        $app->post('/account', AjaxController::class.':updateAccount');
        $app->post('/request-response', AjaxController::class.':respondToRequest');
        $app->post('/upload', AjaxController::class.':uploadImage');
        $app->post('/get-selection-list', AjaxController::class.':getSelectionListForAssociation');
        $app->post('/remove-instructor-organization-access', AjaxController::class.':removeInstructorOrganizationAccess');
    })->add(JsonAuthorizationMiddleware::class.':assertLoggedIn');

    $app->group('', function() use($app){
        $app->post('/get-associations', AjaxController::class.':getAssociations');
        $app->post('/update-admin-status', AjaxController::class.':updateAdminStatus');
        $app->post('/update-associations', AjaxController::class.':updateAssociations');
        $app->post('/user-activation', AjaxController::class.':setUserActivation');
        $app->post('/delete-user', AjaxController::class.':deleteUser');
        $app->post('/page-activation', AjaxController::class.':updatePageActivation');
        $app->post('/delete-page', AjaxController::class.':deletePage');
        $app->post('/get-email-profiles', AjaxController::class.':getEmailProfiles');
        $app->post('/load-event-list', AjaxController::class. ':loadEventListHtml');
    })->add(JsonAuthorizationMiddleware::class.':assertAdmin');
});

$app->get('/events', WebsiteController::class.':loadEvents')->setName('events');
$app->get('/events/{eventId}[/date/{date}]', WebsiteController::class.':loadEvent')->setName('event');
$app->get('/instructors', WebsiteController::class.':loadInstructors')->setName('instructors');
$app->get('/instructors/{instructorId}', WebsiteController::class.':loadInstructor')->setName('instructor');
$app->get('/organizations', WebsiteController::class.':loadOrganizations')->setName('organizations');
$app->get('/organizations/{organizationId}', WebsiteController::class.':loadOrganization')->setName('organization');
$app->get('/newsletter/{newsletterId}', WebsiteController::class.':loadNewsletter')->setName('loadNewsletter');
$app->get('/[{slug:.+}]', WebsiteController::class .':loadPage');
$app->post('/[{slug:.+}]', WebsiteController::class .':processPost');

$app->getContainer()['notFoundHandler'] = function($c){
    return function($request, $response) use ($c){
        /** @var \Slim\Views\Twig $view */
        $view = $c['view'];
        return $view->render($response->withStatus(404), '404.twig');
    };
};

//production only error handlers
if($app->getContainer()->get('settings')['mode'] === 'production') {
    $app->getContainer()['errorHandler'] = function ($c) {
        return function ($request, $response, $error) use ($c) {
            /** @var \Slim\Http\Response $response */
            /** @var \Slim\Views\Twig $view */
            $view = $c['view'];
            /** @var \Monolog\Logger $logger */
            $logger = $c['logger'];
            $logger->error("Error: ", array('exception' => $error));
            return $view->render($response->withStatus(500), '500.twig');
        };
    };
    $app->getContainer()['phpErrorHandler'] = function ($c) {
        return function ($request, $response, $error) use ($c) {
            /** @var \Slim\Http\Response $response */
            /** @var \Slim\Views\Twig $view */
            $view = $c['view'];
            /** @var \Monolog\Logger $logger */
            $logger = $c['logger'];
            $logger->error("Error: ", array('exception' => $error));
            return $view->render($response->withStatus(500), '500.twig');
        };
    };
}