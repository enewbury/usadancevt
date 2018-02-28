<?php
/**
 * Created by Eric Newbury.
 * Date: 7/9/16
 */

namespace EricNewbury\DanceVT\Util;


use EricNewbury\DanceVT\Models\Exceptions\ClientValidationErrorException;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    /** @var  Validator */
    private $validator;
    protected function setUp(){
        $this->validator = new Validator();
    }

    public function testNullPassword(){
        $this->expectException(ClientValidationErrorException::class);
        $this->validator->validatePassword(null);
    }

    public function testTooShortPassword(){
        $this->expectException(ClientValidationErrorException::class);
        $this->validator->validatePassword('3rI@');
    }

    public function testNoNumOrSpecialChar(){
        $this->expectException(ClientValidationErrorException::class);
        $this->validator->validatePassword('ericnewbury');
    }

    public function testWorksWithNum(){
        $this->validator->validatePassword('ericnewbury1');
    }

    public function testWorksWithSpecialChar(){
        $this->validator->validatePassword('ericnewbury$');
    }
}