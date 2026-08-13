<?php

declare(strict_types=1);

namespace Ibs\Context\Communication\Service {
    // Позволяет тесту детерминированно смоделировать ошибку PCRE (preg_replace_callback() -> null),
    // не подменяя сам класс — перехватывает неквалифицированный вызов через фолбэк PHP по неймспейсу.
    function preg_replace_callback(string $pattern, callable $callback, string $subject): ?string
    {
        if (\App\Tests\Ibs\Context\Communication\Service\TemplateResolverPregFailureTest::$forcePregFailure) {
            return null;
        }

        return \preg_replace_callback($pattern, $callback, $subject);
    }
}

namespace App\Tests\Ibs\Context\Communication\Service {

    use Ibs\Context\Communication\Model\NotificationMessage;
    use Ibs\Context\Communication\Repository\NotificationTemplateRepository;
    use Ibs\Context\Communication\Service\TemplateResolver;
    use PHPUnit\Framework\TestCase;

    class TemplateResolverPregFailureTest extends TestCase
    {
        public static bool $forcePregFailure = false;

        protected function tearDown(): void
        {
            self::$forcePregFailure = false;
            parent::tearDown();
        }

        public function testFallsBackToOriginalTextWhenPcreFails(): void
        {
            $templates = $this->createStub(NotificationTemplateRepository::class);
            $resolver = new TemplateResolver($templates);

            self::$forcePregFailure = true;

            $message = new NotificationMessage(body: 'Здравствуйте, %patient_name%!', data: ['patient_name' => 'Иван']);
            $resolved = $resolver->resolve($message, 'sms');

            self::assertSame('Здравствуйте, %patient_name%!', $resolved->body);
        }
    }
}
