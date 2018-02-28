<?php
/**
 * Created by enewbury.
 * Date: 12/15/15
 */

namespace EricNewbury\DanceVT\Util;

use EricNewbury\DanceVT\Models\Exceptions\ClientErrorException;
use EricNewbury\DanceVT\Models\Response\BaseResponse;
use EricNewbury\DanceVT\Models\Response\FailResponse;
use EricNewbury\DanceVT\Models\Response\SuccessResponse;
use Intervention\Image\Constraint;
use Intervention\Image\ImageManager;
use upload;
use Upload\File;
use Upload\Storage\FileSystem;
use Upload\Validation\Mimetype;
use Upload\Validation\Size;

class Uploader
{
    const UPLOAD_FOLDER = 'img/uploads/';


    public static function uploadImage($fileKey){
        $tempName = $_FILES[$fileKey]['tmp_name'];
        try {
            Validator::validateImage($fileKey);
            $ext = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
            $name = uniqid().'.'.$ext;

            $manager = new ImageManager();
            $manager->make($tempName)->widen(1920, function (Constraint $constraint) {
                $constraint->upsize();
            })
            ->save(ROOT_URL.'/../public_html/'.self::UPLOAD_FOLDER.$name)
            ->widen(250, function (Constraint $constraint) {
                $constraint->upsize();
            })
            ->save(ROOT_URL.'/../public_html/'.self::UPLOAD_FOLDER.'thumb/'.$name);
            $res = new SuccessResponse();
            $res->setData([
                'imageUrl'=> '/'.self::UPLOAD_FOLDER.$name,
                'thumbUrl'=>'/'.self::UPLOAD_FOLDER.'thumb/'.$name,
                'message'=>'Image Uploaded'
            ]);
            return $res;
        }
        catch (\Exception $e){
            return new FailResponse($e->getMessage());
        }
    }
}