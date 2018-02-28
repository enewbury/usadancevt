<?php
/**
 * Created by enewbury.
 * Date: 12/15/15
 */

namespace EricNewbury\DanceVT\Util;


use EricNewbury\DanceVT\Config\Db;
use EricNewbury\DanceVT\Models\Response\BaseResponse;
use EricNewbury\DanceVT\Models\Persistence\Instructor;
use EricNewbury\DanceVT\Models\Persistence\Organization;
use EricNewbury\DanceVT\Services\PersistenceService;
use Slim\Route;
use Slim\Slim;

abstract class SessionTools
{

    protected function checkLoggedInRedirect(){
        return function(){
            $app = Slim::getInstance();
            if(!isSet($_SESSION['userId'])){
                $_SESSION['redirectPath'] = $app->request()->getPath();
                $app->redirect('/login');
            }
        };
    }

    protected function checkAdminRedirect(){
        return function(){

            $app = Slim::getInstance();
            $user = $app->view()->getData('user');
            if (!$user || !$user->isApprovedSiteAdmin()) {
                $app->flash('message','You must be an admin to continue');
                $app->redirect('/permission-denied');
            }
        };
    }

    protected function checkAdminJson(){
        return function(){
            $app = Slim::getInstance();
            $user = $app->view()->getData('user');
            if(!$user || !$user->isApprovedSiteAdmin()){
                $res = new BaseResponse();
                $res->setStatus(BaseResponse::FAIL);
                $res->setData([
                    'message'=>'Permission denied. Login as admin.'
                ]);
                echo $res;
                $app->stop();
            }
        };
    }

    protected function checkOrganizationRedirect(){
        return function(){

            $app = Slim::getInstance();
            $user = $app->view()->getData('user');
            if (!$user || !$user->isActiveOrganizationAdmin()) {
                $app->flash('message','You must be an organization admin to continue');
                $app->redirect('/permission-denied');
            }
        };
    }

    protected function checkInstructorRedirect(){
        return function(){

            $app = Slim::getInstance();
            $user = $app->view()->getData('user');
            if (!$user || !$user->isActiveInstructorAdmin()) {
                $app->flash('message','You must be an instructor admin to continue');
                $app->redirect('/permission-denied');
            }
        };
    }

    protected function checkOrganizationJson(){
        return function(){
            $app = Slim::getInstance();
            $user = $app->view()->getData('user');
            if (!$user || !$user->isActiveOrganizationAdmin()) {
                $res = new BaseResponse();
                $res->setStatus(BaseResponse::FAIL);
                $res->setData([
                    'message' => 'Permission denied. Login as organization admin'
                ]);
                echo $res;
                $app->stop();
            }
        };
    }

    protected function checkInstructorJson(){
        return function(){
            $app = Slim::getInstance();
            $user = $app->view()->getData('user');
            if (!$user || !$user->isActiveInstructorAdmin()) {
                $res = new BaseResponse();
                $res->setStatus(BaseResponse::FAIL);
                $res->setData([
                    'message' => 'Permission denied. Login as instructor admin'
                ]);
                echo $res;
                $app->stop();
            }
        };
    }

    protected function checkLoggedInJson() {
        return function () {
            $app = Slim::getInstance();
            if (!isset($_SESSION['userId'])) {
                $response = new BaseResponse();
                $response->setStatus(BaseResponse::FAIL);
                $response->setData(['error'=>'notLoggedIn', 'message'=>'You must be logged in to continue.']);
                echo $response;
                $app->stop();
            }
        };
    }

    protected function checkValidOrganization()
    {
        return function (Route $route){
            $app = Slim::getInstance();
            $orgId = $route->getParam('id');
            $organization = PersistenceService::getOrganization($orgId);
            $app->organization = $organization;
            $user = $app->view()->getData('user');
            $permission = PersistenceService::getUserOrganizationAssociation($user, $organization);

            if($permission == null || !$permission->isApproved()){
                $app->flash('message','You do not have access to this organization');
                $app->redirect('/permission-denied');
            }
        };
    }


    protected function checkActiveOrPendingOrganizationJson()
    {
        return function (){
            $app = Slim::getInstance();
            $orgId = $_POST['organizationId'];
            $organization = PersistenceService::getOrganization($orgId);
            $app->organization = $organization;
            $user = $app->view()->getData('user');
            $permission = PersistenceService::getUserOrganizationAssociation($user, $organization);

            if($permission == null){
                $response = new BaseResponse();
                $response->setStatus(BaseResponse::FAIL);
                $response->setData(['message'=>'You do not have access to this organization.']);
                echo $response;
                $app->stop();
            }
        };
    }

    protected function checkValidInstructor()
    {
        return function (Route $route){
            $app = Slim::getInstance();
            $id = $route->getParam('id');
            $instructor = PersistenceService::getInstructor($id);
            $app->instructor = $instructor;
            $user = $app->view()->getData('user');
            $permission = PersistenceService::getUserInstructorAssociation($user, $instructor);

            if($permission == null || !$permission->isApproved()){
                $app->flash('message','You do not have access to this instructor');
                $app->redirect('/permission-denied');
            }
        };
    }
    protected function checkActiveOrPendingInstructorJson()
    {
        return function (){
            $app = Slim::getInstance();
            $id = $_POST['instructorId'];
            $instructor = PersistenceService::getInstructor($id);
            $app->instructor = $instructor;
            $user = $app->view()->getData('user');
            $permission = PersistenceService::getUserInstructorAssociation($user, $instructor);

            if($permission == null){
                $response = new BaseResponse();
                $response->setStatus(BaseResponse::FAIL);
                $response->setData(['message'=>'You do not have access to this instructor.']);
                echo $response;
                $app->stop();
            }
        };
    }

    protected function checkAdminOrOrgAdminWithPostedId()
    {
        return function() {
            $app = Slim::getInstance();
            $orgId = $_POST['organizationId'];
            $user = $app->view()->getData('user');

            if ($user && $user->isApprovedSiteAdmin()) {
                return;
            }

            $association = PersistenceService::getUserOrganizationAssociation($user, Db::getInstance()->getReference(Organization::class, $orgId));
            if ($association !== null) {
                if ($association->isApproved()) {
                    return;
                }
            }

            $res = new BaseResponse();
            $res->setStatus(BaseResponse::FAIL);
            $res->setData([
                'message' => 'Permission denied. Login as admin or organization admin'
            ]);
            echo $res;
            $app->stop();
        };
    }

    protected function checkAdminOrInstrAdminWithPostedId()
    {
        return function () {
            $app = Slim::getInstance();
            $instId = $_POST['instructorId'];
            $user = $app->view()->getData('user');

            if ($user && $user->isApprovedSiteAdmin()) {
                return;
            }

            $association = PersistenceService::getUserInstructorAssociation($user, Db::getInstance()->getReference(Instructor::class, $instId));
            if ($association !== null) {
                if ($association->isApproved()) {
                    return;
                }
            }

            $res = new BaseResponse();
            $res->setStatus(BaseResponse::FAIL);
            $res->setData([
                'message' => 'Permission denied. Login as admin or instructor admin'
            ]);
            echo $res;
            $app->stop();
        };
    }
}