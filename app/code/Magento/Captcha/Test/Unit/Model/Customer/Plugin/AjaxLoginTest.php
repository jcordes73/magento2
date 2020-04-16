<?php declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Captcha\Test\Unit\Model\Customer\Plugin;

use Magento\Captcha\Helper\Data;
use Magento\Captcha\Model\Customer\Plugin\AjaxLogin;
use Magento\Captcha\Model\DefaultModel;
use Magento\Checkout\Model\Session;
use Magento\Customer\Controller\Ajax\Login;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AjaxLoginTest extends TestCase
{
    /**
     * @var MockObject|Session
     */
    protected $sessionManagerMock;

    /**
     * @var MockObject|Data
     */
    protected $captchaHelperMock;

    /**
     * @var MockObject|JsonFactory
     */
    protected $jsonFactoryMock;

    /**
     * @var MockObject
     */
    protected $captchaMock;

    /**
     * @var MockObject
     */
    protected $resultJsonMock;

    /**
     * @var MockObject
     */
    protected $requestMock;

    /**
     * @var MockObject|Login
     */
    protected $loginControllerMock;

    /**
     * @var MockObject|Json
     */
    protected $serializerMock;

    /**
     * @var array
     */
    protected $formIds;

    /**
     * @var AjaxLogin
     */
    protected $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->sessionManagerMock = $this->createPartialMock(Session::class, ['setUsername']);
        $this->captchaHelperMock = $this->createMock(Data::class);
        $this->captchaMock = $this->createMock(DefaultModel::class);
        $this->jsonFactoryMock = $this->createPartialMock(
            JsonFactory::class,
            ['create']
        );
        $this->resultJsonMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);
        $this->requestMock = $this->createMock(Http::class);
        $this->loginControllerMock = $this->createMock(Login::class);

        $this->loginControllerMock->expects($this->any())->method('getRequest')
            ->will($this->returnValue($this->requestMock));

        $this->captchaHelperMock
            ->expects($this->exactly(1))
            ->method('getCaptcha')
            ->will($this->returnValue($this->captchaMock));

        $this->formIds = ['user_login'];
        $this->serializerMock = $this->createMock(Json::class);

        $this->model = new AjaxLogin(
            $this->captchaHelperMock,
            $this->sessionManagerMock,
            $this->jsonFactoryMock,
            $this->formIds,
            $this->serializerMock
        );
    }

    /**
     * Test aroundExecute.
     */
    public function testAroundExecute()
    {
        $username = 'name';
        $captchaString = 'string';
        $requestData = [
            'username' => $username,
            'captcha_string' => $captchaString,
            'captcha_form_id' => $this->formIds[0]
        ];
        $requestContent = json_encode($requestData);

        $this->requestMock->expects($this->once())->method('getContent')->will($this->returnValue($requestContent));
        $this->captchaMock->expects($this->once())->method('isRequired')->with($username)
            ->will($this->returnValue(true));
        $this->captchaMock->expects($this->once())->method('logAttempt')->with($username);
        $this->captchaMock->expects($this->once())->method('isCorrect')->with($captchaString)
            ->will($this->returnValue(true));
        $this->serializerMock->expects($this->once())->method('unserialize')->will($this->returnValue($requestData));

        $closure = function () {
            return 'result';
        };

        $this->captchaHelperMock
            ->expects($this->exactly(1))
            ->method('getCaptcha')
            ->with('user_login')
            ->will($this->returnValue($this->captchaMock));

        $this->assertEquals('result', $this->model->aroundExecute($this->loginControllerMock, $closure));
    }

    /**
     * Test aroundExecuteIncorrectCaptcha.
     */
    public function testAroundExecuteIncorrectCaptcha()
    {
        $username = 'name';
        $captchaString = 'string';
        $requestData = [
            'username' => $username,
            'captcha_string' => $captchaString,
            'captcha_form_id' => $this->formIds[0]
        ];
        $requestContent = json_encode($requestData);

        $this->requestMock->expects($this->once())->method('getContent')->will($this->returnValue($requestContent));
        $this->captchaMock->expects($this->once())->method('isRequired')->with($username)
            ->will($this->returnValue(true));
        $this->captchaMock->expects($this->once())->method('logAttempt')->with($username);
        $this->captchaMock->expects($this->once())->method('isCorrect')
            ->with($captchaString)->will($this->returnValue(false));
        $this->serializerMock->expects($this->once())->method('unserialize')->will($this->returnValue($requestData));

        $this->sessionManagerMock->expects($this->once())->method('setUsername')->with($username);
        $this->jsonFactoryMock->expects($this->once())->method('create')
            ->will($this->returnValue($this->resultJsonMock));

        $this->resultJsonMock
            ->expects($this->once())
            ->method('setData')
            ->with(['errors' => true, 'message' => __('Incorrect CAPTCHA')])
            ->will($this->returnSelf());

        $closure = function () {
        };
        $this->assertEquals($this->resultJsonMock, $this->model->aroundExecute($this->loginControllerMock, $closure));
    }

    /**
     * @dataProvider aroundExecuteCaptchaIsNotRequired
     * @param string $username
     * @param array $requestContent
     */
    public function testAroundExecuteCaptchaIsNotRequired($username, $requestContent)
    {
        $this->requestMock->expects($this->once())->method('getContent')
            ->will($this->returnValue(json_encode($requestContent)));
        $this->serializerMock->expects($this->once())->method('unserialize')
            ->will($this->returnValue($requestContent));

        $this->captchaMock->expects($this->once())->method('isRequired')->with($username)
            ->will($this->returnValue(false));
        $this->captchaMock->expects($this->never())->method('logAttempt')->with($username);
        $this->captchaMock->expects($this->never())->method('isCorrect');

        $closure = function () {
            return 'result';
        };
        $this->assertEquals('result', $this->model->aroundExecute($this->loginControllerMock, $closure));
    }

    /**
     * @return array
     */
    public function aroundExecuteCaptchaIsNotRequired(): array
    {
        return [
            [
                'username' => 'name',
                'requestData' => ['username' => 'name', 'captcha_string' => 'string'],
            ],
            [
                'username' => 'name',
                'requestData' => [
                        'username' => 'name',
                        'captcha_string' => 'string',
                        'captcha_form_id' => $this->formIds[0]
                    ],
            ],
            [
                'username' => null,
                'requestData' => [
                        'username' => null,
                        'captcha_string' => 'string',
                        'captcha_form_id' => $this->formIds[0]
                    ],
            ],
            [
                'username' => 'name',
                'requestData' => [
                        'username' => 'name',
                        'captcha_string' => 'string',
                        'captcha_form_id' => null
                    ],
            ],
        ];
    }
}
