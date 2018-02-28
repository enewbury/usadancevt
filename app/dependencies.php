<?php
// DIC configuration

use Cocur\Slugify\Bridge\Twig\SlugifyExtension;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Tools\Setup;
use EricNewbury\DanceVT\Controllers\AdminPanelController;
use EricNewbury\DanceVT\Controllers\AjaxController;
use EricNewbury\DanceVT\Controllers\InstructorPanelController;
use EricNewbury\DanceVT\Controllers\OrganizationPanelController;
use EricNewbury\DanceVT\Controllers\UserAccountController;
use EricNewbury\DanceVT\Services\AdminTool;
use EricNewbury\DanceVT\Services\AssociationsTool;
use EricNewbury\DanceVT\Controllers\RegistrationController;
use EricNewbury\DanceVT\Controllers\WebsiteController;
use EricNewbury\DanceVT\Services\EventTool;
use EricNewbury\DanceVT\Services\FilteringService;
use EricNewbury\DanceVT\Services\InstructorTool;
use EricNewbury\DanceVT\Services\Mail\MailService;
use EricNewbury\DanceVT\Services\OrganizationTool;
use EricNewbury\DanceVT\Services\PersistenceService;
use EricNewbury\DanceVT\Services\RegistrationService;
use EricNewbury\DanceVT\Services\UserAccountService;
use EricNewbury\DanceVT\Util\AuthorizationTool;
use EricNewbury\DanceVT\Util\Middleware\AuthorizationMiddleware;
use EricNewbury\DanceVT\Util\Middleware\BaseMiddleware;
use EricNewbury\DanceVT\Util\Middleware\HtmlAuthorizationMiddleware;
use EricNewbury\DanceVT\Util\Middleware\JsonAuthorizationMiddleware;
use EricNewbury\DanceVT\Util\Validator;

class CustomContainer extends \Slim\Container{
    /** @var  EntityManager */
    public $db;
    /** @var  \Slim\Views\Twig */
    public $view;
    /** @var  \Monolog\Logger */
    public $logger;

}

$container = $app->getContainer();

// -----------------------------------------------------------------------------
// Service providers
// -----------------------------------------------------------------------------

// Twig
/** @param CustomContainer $c
 * @return \Slim\Views\Twig
 */
$container['view'] = function ($c) {
    $settings = $c->settings;
    $view = new Slim\Views\Twig($settings['view']['template_path'], $settings['view']['twig']);

    // Add extensions
    $view->addExtension(new Slim\Views\TwigExtension($c->router, $c->request->getUri()));
    $view->addExtension(new Twig_Extension_Debug());
    $view->addExtension(new SlugifyExtension(Slugify::create()));
    $view->getEnvironment()->addFilter(new Twig_SimpleFilter('phone', function ($num) {
        return ($num)?'('.substr($num,0,3).') '.substr($num,3,3).'-'.substr($num,6,4):'&nbsp;';
    }));

    return $view;
};


// -----------------------------------------------------------------------------
// Service factories
// -----------------------------------------------------------------------------

// monolog
/** @param CustomContainer $c
 * @return \Monolog\Logger
 */
$container['logger'] = function ($c) {
    $settings = $c->settings;
    $logger = new Monolog\Logger($settings['logger']['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['logger']['path'], Monolog\Logger::DEBUG));
    return $logger;
};

//doctrine
/** @param CustomContainer $c
 * @return EntityManager
 */
$container['db'] = function ($c) {
    $settings = $c->settings;
    $isDevMode = ($settings["mode"] == "development") ? true : false;

    $config = Setup::createAnnotationMetadataConfiguration($settings['doctrine']['model_paths'], $isDevMode, $settings['cache.dir']);
    $config->setNamingStrategy(new UnderscoreNamingStrategy());

    return EntityManager::create($settings['doctrine'], $config);
};

/**
 * @param CustomContainer $c
 * @return PersistenceService
 */
$container[PersistenceService::class] = function($c){
    return new PersistenceService($c->db, $c->logger);
};


/**
 * @param CustomContainer $c
 * @return Validator
 */
$container[Validator::class] = function($c){
    return new Validator();
};

/**
 * @param CustomContainer $c
 * @return HTMLPurifier
 */
$container[HTMLPurifier::class] = function($c){
    $config = HTMLPurifier_Config::createDefault();
    //make the cache file if it doesn't exist
    if (!file_exists($c->settings['cache.dir'].'/purifier')) {
        mkdir($c->settings['cache.dir'].'/purifier', 0755);
    }
    $config->set('Cache.SerializerPath', $c->settings['cache.dir'].'/purifier');
    $config->set('HTML.DefinitionID', 'captions-definition');
    if($def = $config->maybeGetRawHTMLDefinition()){
        $def->addElement('figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common');
        $def->addElement('figcaption', 'Inline', 'Flow', 'Common');
    }
    return new HTMLPurifier($config);
};

/**
 * @param CustomContainer $c
 * @return RegistrationService
 */
$container[RegistrationService::class] = function($c){
    return new RegistrationService(
        $c->logger,
        $c->get(PersistenceService::class),
        $c->get(Validator::class),
        $c->get(MailService::class)
    );
};


/**
 * @param CustomContainer $c
 * @return UserAccountService
 */
$container[UserAccountService::class] = function($c){
    return new UserAccountService(
        $c->logger,
        $c->get(PersistenceService::class),
        $c->get(Validator::class),
        $c->get(MailService::class),
        $c->get(AdminTool::class),
        $c->get(AssociationsTool::class),
        $c->get(InstructorTool::class),
        $c->get(OrganizationTool::class)
    );
};

/**
 * @param CustomContainer $c
 * @return MailService
 */
$container[MailService::class] = function($c){
    return new MailService(
        $c->view->getEnvironment(),
        $c->get(PersistenceService::class),
        $c->settings['mail']
    );
};

/**
 * @param CustomContainer $c
 * @return AuthorizationTool
 */
$container[AuthorizationTool::class] = function($c){
    return new AuthorizationTool($c->get(PersistenceService::class));
};

/**
 * @param CustomContainer $c
 * @return AdminTool
 */
$container[AdminTool::class] = function($c){
    return new AdminTool(
        $c->get(PersistenceService::class),
        $c->get(MailService::class),
        $c->get(Validator::class),
        $c->logger
    );
};

/**
 * @param CustomContainer $c
 * @return AssociationsTool
 */
$container[AssociationsTool::class] = function($c){
    return new AssociationsTool(
        $c->get(PersistenceService::class),
        $c->get(MailService::class),
        $c->get(AuthorizationTool::class)
    );
};

/**
 * @param CustomContainer $c
 * @return InstructorTool
 */
$container[InstructorTool::class] = function($c){
    return new InstructorTool(
        $c->get(PersistenceService::class),
        $c->get(AssociationsTool::class),
        $c->get(MailService::class),
        $c->get(Validator::class),
        $c->get(HTMLPurifier::class)
    );
};

/**
 * @param CustomContainer $c
 * @return OrganizationTool
 */
$container[OrganizationTool::class] = function($c){
    return new OrganizationTool(
        $c->get(PersistenceService::class),
        $c->get(AssociationsTool::class),
        $c->get(MailService::class),
        $c->get(Validator::class),
        $c->get(HTMLPurifier::class)
    );
};

/**
 * @param CustomContainer $c
 * @return EventTool
 */
$container[EventTool::class] = function($c){
    return new EventTool(
        $c->get(PersistenceService::class),
        $c->get(AssociationsTool::class),
        $c->get(MailService::class),
        $c->get(Validator::class),
        $c->get(HTMLPurifier::class),
        $c->logger
    );
};

/**
 * @param CustomContainer $c
 * @return FilteringService
 */
$container[FilteringService::class] = function($c) {
    return new FilteringService($c->get(PersistenceService::class));
};

// -----------------------------------------------------------------------------
// Routing Service factories
// -----------------------------------------------------------------------------

/**
 * @param CustomContainer $c
 * @return WebsiteController
 */
$container[WebsiteController::class] = function($c){
    return new WebsiteController(
        $c->view,
        $c->get(PersistenceService::class),
        $c->get(EventTool::class),
        $c->get(FilteringService::class),
        $c->get(Validator::class),
        $c->get(MailService::class)
    );
};



/**
 * @param CustomContainer $c
 * @return RegistrationController
 */
$container[RegistrationController::class] = function($c){
    return new RegistrationController(
        $c->view,
        $c->router,
        $c->get(RegistrationService::class)
    );
};

/**
 * @param CustomContainer $c
 * @return UserAccountController
 */
$container[UserAccountController::class] = function($c){
    return new UserAccountController(
        $c->view,
        $c->router,
        $c->get(UserAccountService::class),
        $c->get(PersistenceService::class)
    );
};

/**
 * @param CustomContainer $c
 * @return AdminPanelController
 */
$container[AdminPanelController::class] = function($c){
    return new AdminPanelController(
        $c->view,
        $c->router,
        $c->get(PersistenceService::class),
        $c->get(FilteringService::class),
        $c->get(AdminTool::class),
        $c->get(AssociationsTool::class),
        $c->get(OrganizationTool::class),
        $c->get(InstructorTool::class),
        $c->get(EventTool::class),
        $c->get(HTMLPurifier::class),
        $c->get(MailService::class)
    );
};

/**
 * @param CustomContainer $c
 * @return OrganizationPanelController
 */
$container[OrganizationPanelController::class] = function($c){
    return new OrganizationPanelController(
        $c->view,
        $c->router,
        $c->get(PersistenceService::class),
        $c->get(UserAccountService::class),
        $c->get(OrganizationTool::class),
        $c->get(EventTool::class),
        $c->get(FilteringService::class)
    );
};

/**
 * @param CustomContainer $c
 * @return InstructorPanelController
 */
$container[InstructorPanelController::class] = function($c){
    return new InstructorPanelController(
        $c->view,
        $c->router,
        $c->get(PersistenceService::class),
        $c->get(UserAccountService::class),
        $c->get(InstructorTool::class),
        $c->get(EventTool::class),
        $c->get(FilteringService::class)
    );
};

/**
 * @param CustomContainer $c
 * @return AjaxController
 */
$container[AjaxController::class] = function($c){
    return new AjaxController(
        $c->view,
        $c->get(PersistenceService::class),
        $c->get(AssociationsTool::class),
        $c->get(UserAccountService::class),
        $c->get(InstructorTool::class),
        $c->get(OrganizationTool::class),
        $c->get(EventTool::class),
        $c->get(FilteringService::class)
    );
};

// -----------------------------------------------------------------------------
// Middleware Service factories
// -----------------------------------------------------------------------------

/**
 * @param CustomContainer $c
 * @return BaseMiddleware
 */
$container[BaseMiddleware::class] = function($c){
    return new BaseMiddleware(
        $c->get(PersistenceService::class),
        $c->view,
        $c->get('notFoundHandler'),
        $c->get(UserAccountService::class)
    );
};

/**
 * @param CustomContainer $c
 * @return AuthorizationMiddleware
 */
$container[HtmlAuthorizationMiddleware::class] = function ($c){
    return new HtmlAuthorizationMiddleware(
        $c->get(AuthorizationTool::class),
        $c->view,
        $c->router
    );
};

/**
 * @param CustomContainer $c
 * @return AuthorizationMiddleware
 */
$container[JsonAuthorizationMiddleware::class] = function ($c){
    return new JsonAuthorizationMiddleware($c->get(AuthorizationTool::class), $c->view);
};
