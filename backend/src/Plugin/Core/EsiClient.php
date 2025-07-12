<?php

declare(strict_types=1);

namespace Neucore\Plugin\Core;

use Neucore\Exception\RuntimeException;
use Neucore\Factory\HttpClientFactoryInterface;
use Neucore\Service\EsiClient as EsiClientService;
use Neucore\Storage\StorageInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;

class EsiClient implements EsiClientInterface
{
    /**
     * @see \Neucore\Controller\App\EsiController::$errorLimitRemain
     * @see \Neucore\Command\Traits\EsiRateLimited::$errorLimitRemaining
     */
    private int $errorLimitRemaining = 15;

    private ?string $compatibilityDate = null;

    public function __construct(
        private readonly EsiClientService           $esiClient,
        private readonly HttpClientFactoryInterface $httpClientFactory,
        private readonly StorageInterface           $storage,
    ) {}

    public function getErrorLimitRemaining(): int
    {
        return $this->errorLimitRemaining;
    }

    public function setCompatibilityDate(string $compatibilityDate): void
    {
        $this->compatibilityDate = $compatibilityDate;
    }

    public function request(
        string $esiPath,
        string $method = 'GET',
        ?string $body = null,
        ?int $characterId = null,
        string $eveLoginName = self::DEFAULT_LOGIN_NAME,
        bool $debug = false,
        ?string $compatibilityDate = null,
    ): ResponseInterface {
        if (($retryAt1 = EsiClientService::getErrorLimitWaitTime($this->storage, $this->errorLimitRemaining)) > 0) {
            throw new Exception(EsiClientInterface::ERROR_ERROR_LIMIT_REACHED, $retryAt1);
        }
        if (($retryAt2 = EsiClientService::getRateLimitWaitTime($this->storage)) > time()) {
            throw new Exception(EsiClientInterface::ERROR_RATE_LIMIT_REACHED, $retryAt2);
        }
        if (($retryAt3 = EsiClientService::getThrottledWaitTime($this->storage)) > time()) {
            throw new Exception(EsiClientInterface::ERROR_TEMPORARILY_THROTTLED, $retryAt3);
        }

        try {
            $response = $this->esiClient->request(
                $esiPath,
                $method,
                $body,
                $characterId,
                $eveLoginName,
                $debug,
                $compatibilityDate ?: $this->compatibilityDate,
            );
        } catch (RuntimeException $e) {
            if ($e->getCode() === 568420) {
                throw new Exception(EsiClientInterface::ERROR_CHARACTER_NOT_FOUND);
            } elseif ($e->getCode() === 568421) {
                throw new Exception(EsiClientInterface::ERROR_INVALID_TOKEN);
            } else {
                throw new Exception(EsiClientInterface::ERROR_UNKNOWN);
            }
        } catch (ClientExceptionInterface $e) {
            $response = $this->httpClientFactory->createResponse(
                500, // status
                [], // header
                $e->getMessage(), // body
            );
        }

        return $response;
    }
}
