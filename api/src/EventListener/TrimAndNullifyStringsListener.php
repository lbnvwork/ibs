<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

class TrimAndNullifyStringsListener
{
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            return;
        }
        $content = $request->getContent();
        if (empty($content)) {
            return;
        }
        $data = json_decode($content, true);
        if (!is_array($data)) {
            return;
        }
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    $value = null;
                } else {
                    $value = $trimmed;
                }
            }
        });
        $request->initialize(
            $request->query->all(),
            $request->request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            json_encode($data)
        );
    }
}