<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\User;
use App\Service\JwtService;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Random\RandomException;
use App\Service\MailService;
use App\Model\PasswordResetToken;
use Hyperf\Contract\ConfigInterface;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly JwtService $jwt,
        private readonly MailService $mail,
        private readonly ConfigInterface $config,
    )
    {

    }

    /**
     * @return ResponseInterface
     * @throws RandomException
     */
    public function register(): ResponseInterface
    {
        $name = trim((string) $this->request->input('name', ''));
        $email = strtolower(trim((string) $this->request->input('email', '')));
        $password = (string) $this->request->input('password', '');
        $passwordConfirmation = (string) $this->request->input('password_confirmation', '');

        $errors = [];

        if ($name === '' || mb_strlen($name) > 255) {
            $errors['name'] = ['姓名必填且不能超过255个字符'];
        }

        if ( ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            $errors['email'] = ['请输入有效邮箱。'];
        } elseif (User::withTrashed()->where('email', $email)->exists()) {
            $errors['email'] = ['该邮箱已注册。'];
        }

        if (strlen($password) < 8) {
            $errors['password'] = ['密码至少需要8个字符'];
        } elseif ($password !== $passwordConfirmation) {
            $errors['password'] = ['两次输入的密码不一致。'];
        }

        if ($errors !== []) {
            return $this->response->json(['message' => '验证失败', 'errors' => $errors])->withStatus(422);

        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $mailSent = true;

        try {
            $this->sendVerificationEmail($user);
        } catch (\Throwable) {
            $mailSent = false;
        }

        return $this->response->json([
            'message' => '注册成功',
            ...$this->jwt->issue($user->id),
            'user' => $this->userData($user),
            'verification_email_sent' => $mailSent,
        ])->withStatus(201);
    }

    /**
     * @return ResponseInterface
     * @throws RandomException
     */
    public function login()
    {
        $email = strtolower((string) $this->request->input('email', ''));
        $password = (string) $this->request->input('password', '');
        if ( ! filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            return $this->response->json(['message' => '邮箱或密码错误。'])->withStatus(422);
        }

        $user = User::query()->where('email', $email)->first();
        if ($user === null || ! password_verify($password, $user->password)) {
            return $this->response->json(['message' => '邮箱或密码错误。'])->withStatus(422);
        }

        return $this->response->json([
            'message' => '登录成功',
            ...$this->jwt->issue($user->id),
            'user' => $this->userData($user),
        ]);
    }

    /**
     * @return ResponseInterface
     */
    public function me(): ResponseInterface
    {
        /** @var User $user */
        $user = $this->request->getAttribute('auth_user');
        return $this->response->json(['user' => $this->userData($user)]);
    }

    /**
     * @return ResponseInterface
     */
    public function updateProfile(): ResponseInterface
    {
        /** @var User $user */
        $user = $this->request->getAttribute('auth_user');
        $name = trim((string) $this->request->input('name', ''));
        if ($name === '' || mb_strlen($name) > 255) {
            return $this->response->json([
                'message' => '验证失败',
                'errors' => ['name' => ['姓名必填且不能超过255个字符。']],
            ])->withStatus(422);

        }

        $user->name = $name;
        $user->save();

        return $this->response->json([
            'message' => '个人信息已更新。',
            'user' => $this->userData($user),
        ]);
    }

    /**
     * @return ResponseInterface
     */
    public function updatePassword(): ResponseInterface
    {
        /** @var User $user */
        $user = $this->request->getAttribute('auth_user');
        $currentPassword = (string) $this->request->input('current_password', '');
        $password = (string) $this->request->input('password', '');
        $confirmation = (string) $this->request->input('password_confirmation', '');

        if (! password_verify($currentPassword, $user->password)) {
            return $this->response->json([
                'message' => '验证失败',
                'errors' => ['current_password' => ['当前密码不正确。']],
            ])->withStatus(422);
        }

        if (strlen($password) < 8 || $password !== $confirmation) {
            return $this->response->json([
                'message' => '验证失败',
                'errors' => ['password' => ['新密码至少需要8个字符。']],
            ])->withStatus(422);
        }

        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $user->save();

        return $this->response->json(['message' => '密码已更新。']);
    }

    /**
     * @return ResponseInterface
     */
    public function resendVerification(): ResponseInterface
    {
        /** @var User $user */
        $user = $this->request->getAttribute('auth_user');

        if ($user->email_verified_at !== null) {
            return $this->response->json(['message' => '邮箱已经验证。']);
        }

        try {
            $this->sendVerificationEmail($user);
        } catch (\Throwable) {
            return $this->response->json(['message' => '验证邮件发送失败，请稍后重试。'])
                ->withStatus(503);
        }

        return $this->response->json(['message' => '验证邮件已发送。'])->withStatus(202);
    }

    /**
     * @return ResponseInterface
     */
    public function verifyEmail(): ResponseInterface
    {
        $token = (string) $this->request->input('token', '');

        try {
            $claims = $this->jwt->decode($token);
        } catch (\Throwable) {
            return $this->response->json(['message' => '验证链接无效或已过期。'])
                ->withStatus(400);
        }

        $userId = filter_var($claims->sub ?? null, FILTER_VALIDATE_INT);
        $expectedIssuer = (string) \Hyperf\Support\make(\Hyperf\Contract\ConfigInterface::class)
            ->get('jwt.issuer', 'hyperfVueLearning');

        if (
            ($claims->iss ?? null) !== $expectedIssuer
            || ($claims->purpose ?? null) !== 'email_verification'
            || $userId === false
            || $userId < 1
        ) {
            return $this->response->json(['message' => '验证链接无效或已过期。'])
                ->withStatus(400);
        }

        $user = User::query()->find($userId);
        $emailHash = hash('sha256', strtolower(trim((string) $user?->email)));

        if (
            $user === null
            || ! hash_equals((string) ($claims->email_hash ?? ''), $emailHash)
        ) {
            return $this->response->json(['message' => '验证链接无效或已过期。'])
                ->withStatus(400);
        }

        if ($user->email_verified_at === null) {
            $user->email_verified_at = date('Y-m-d H:i:s');
            $user->save();
        }

        return $this->response->json(['message' => '邮箱验证成功。']);
    }

    /**
     * @param User $user
     * @return void
     */
    private function sendVerificationEmail(User $user): void
    {
        $token = $this->jwt->issueEmailVerification($user->id, $user->email);
        $url = (string) $this->request->getUri()
            ->withPath('/api/auth/verify-email')
            ->withQuery('token=' . rawurlencode($token));
        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

        $this->mail->send(
            $user->email,
            '验证你的邮箱',
            "请在 24 小时内打开此链接完成验证：\n{$url}",
            "<p>请在 24 小时内点击以下链接完成邮箱验证：</p><p><a href=\"{$safeUrl}\">验证邮箱</a></p>",
        );
    }

    /**
     * @return ResponseInterface
     * @throws RandomException
     */
    public function sendPasswordResetLink(): ResponseInterface
    {
        $email = strtolower(trim((string) $this->request->input('email', '')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->json([
                'message' => '请输入有效邮箱。',
            ])->withStatus(422);
        }

        $genericResponse = [
            'message' => '如果该邮箱已注册，重置邮件将发送。',
        ];

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return $this->response->json($genericResponse)->withStatus(202);
        }

        $resetRecord = PasswordResetToken::query()
            ->where('email', $email)
            ->first();

        if ($resetRecord !== null) {
            $createdAt = $resetRecord->created_at?->getTimestamp();

            if ($createdAt !== null && $createdAt > time() - 60) {
                return $this->response->json($genericResponse)->withStatus(202);
            }
        } else {
            $resetRecord = new PasswordResetToken();
            $resetRecord->email = $email;
        }

        $token = bin2hex(random_bytes(32));
        $resetRecord->token = password_hash($token, PASSWORD_BCRYPT);
        $resetRecord->created_at = date('Y-m-d H:i:s');
        $resetRecord->save();

        $resetUrl = rtrim(
                (string) $this->config->get('mail.frontend_url', 'http://localhost:5173'),
                '/',
            ) . '/reset-password?' . http_build_query([
                'email' => $email,
                'token' => $token,
            ]);

        try {
            $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

            $this->mail->send(
                $email,
                '重置密码',
                "请在 60 分钟内打开此链接重置密码：\n{$resetUrl}",
                "<p>请在 60 分钟内点击以下链接重置密码：</p><p><a href=\"{$safeUrl}\">重置密码</a></p>",
            );
        } catch (\Throwable) {
            $resetRecord->delete();
        }

        return $this->response->json($genericResponse)->withStatus(202);
    }

    /**
     * @return ResponseInterface
     * @throws Exception
     */
    public function resetPassword(): ResponseInterface
    {
        $email = strtolower(trim((string) $this->request->input('email', '')));
        $token = (string) $this->request->input('token', '');
        $password = (string) $this->request->input('password', '');
        $confirmation = (string) $this->request->input('password_confirmation', '');

        if (
            ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || $token === ''
            || strlen($password) < 8
            || $password !== $confirmation
        ) {
            return $this->response->json(['message' => '重置信息无效或已过期。'])
                ->withStatus(422);
        }

        $user = User::query()->where('email', $email)->first();
        $resetRecord = PasswordResetToken::query()
            ->where('email', $email)
            ->first();

        $createdAt = $resetRecord?->created_at?->getTimestamp();

        if (
            $user === null
            || $resetRecord === null
            || $createdAt === null
            || $createdAt < time() - 3600
            || ! password_verify($token, $resetRecord->token)
        ) {
            return $this->response->json(['message' => '重置信息无效或已过期。'])
                ->withStatus(422);
        }

        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $user->save();
        $resetRecord->delete();

        return $this->response->json(['message' => '密码已重置，请使用新密码登录。']);
    }

    /**
     * @param User $user
     * @return array
     */
    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified' => $user->email_verified_at !== null,
        ];
    }
}
