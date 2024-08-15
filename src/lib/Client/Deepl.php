<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\AutomatedTranslation\Client;

use GuzzleHttp\Client;
use Ibexa\AutomatedTranslation\Exception\ClientNotConfiguredException;
use Ibexa\AutomatedTranslation\Exception\InvalidLanguageCodeException;
use Ibexa\Contracts\AutomatedTranslation\Client\ClientInterface;

class Deepl implements ClientInterface
{
    private string $authKey;

    private string $baseUri = 'https://api.deepl.com/';

    public function getServiceAlias(): string
    {
        return 'deepl';
    }

    public function getServiceFullName(): string
    {
        return 'Deepl';
    }

    /**
     * @param array{authKey?: string} $configuration
     */
    public function setConfiguration(array $configuration): void
    {
        if (!isset($configuration['authKey'])) {
            throw new ClientNotConfiguredException('authKey is required');
        }
        $this->authKey = $configuration['authKey'];

        if (isset($configuration['baseUri'])) {
            $this->baseUri = $configuration['baseUri'];
        }

    }

    public function translate(string $payload, ?string $from, string $to): string
    {
        $parameters = [
            'auth_key' => $this->authKey,
            'target_lang' => $this->normalized($to),
            'tag_handling' => 'xml',
            'text' => $payload,
        ];

        if (null !== $from) {
            $parameters += [
                'source_lang' => $this->normalized($from),
            ];
        }

        $http = new Client(
            [
                'base_uri' => $this->baseUri,
                'timeout' => 5.0,
            ]
        );
        $response = $http->post('/v2/translate', ['form_params' => $parameters]);
        // May use the native json method from guzzle
        $json = json_decode($response->getBody()->getContents());

        return $json->translations[0]->text;
    }

    public function supportsLanguage(string $languageCode): bool
    {
        return \in_array($this->normalized($languageCode), self::LANGUAGE_CODES);
    }

    private function normalized(string $languageCode): string
    {
        if (\in_array($languageCode, self::LANGUAGE_CODES)) {
            return $languageCode;
        }

        $code = strtoupper(substr($languageCode, 0, 2));
        if (\in_array($code, self::LANGUAGE_CODES)) {
            return $code;
        }

        throw new InvalidLanguageCodeException($languageCode, $this->getServiceAlias());
    }

    /**
     * List of available code https://www.deepl.com/api.html.
     */
    private const LANGUAGE_CODES = ['EN', 'DE', 'FR', 'ES', 'IT', 'NL', 'PL', 'JA'];
}
