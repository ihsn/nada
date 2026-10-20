<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Raised when the external semantic search API returns an error.
 * Carries request/response bodies for debug output when nada_ai_debug is enabled.
 */
class Semantic_search_api_exception extends RuntimeException
{
    /** @var string */
    public $url;

    /** @var int */
    public $http_status;

    /** @var array Request body sent to the semantic API (decoded array). */
    public $request;

    /** @var string Raw response body from the semantic API. */
    public $response_raw;

    /** @var array|null Decoded JSON response when parseable. */
    public $response;

    public function __construct(
        string $message,
        string $url,
        int $http_status,
        array $request,
        string $response_raw = ''
    ) {
        parent::__construct($message, $http_status);
        $this->url           = $url;
        $this->http_status   = $http_status;
        $this->request       = $request;
        $this->response_raw  = $response_raw;
        $decoded             = json_decode($response_raw, true);
        $this->response      = is_array($decoded) ? $decoded : null;
    }

    /** nada-ai's contract error code (error.code of its error envelope, e.g. cursor_invalid), or null. */
    public function error_code(): ?string
    {
        $code = $this->response['error']['code'] ?? null;

        return is_string($code) ? $code : null;
    }

    /**
     * Debug payload for API / UI consumers.
     *
     * @return array<string, mixed>
     */
    public function debug_payload(): array
    {
        return array(
            'url'         => $this->url,
            'http_status' => $this->http_status,
            'request'     => $this->request,
            'response'    => $this->response !== null ? $this->response : $this->response_raw,
        );
    }
}

/**
 * nada-ai was not called because an earlier request found it down (see Nada_ai_link): the request is treated as an
 * outage without waiting for another timeout.
 */
class Nada_ai_unavailable_exception extends Semantic_search_api_exception
{
}

/**
 * A cursor given to a search it cannot page: one the database search must serve (it pages by offset), so the cursor came
 * from another search. The caller starts again from the first page.
 */
class Search_cursor_exception extends InvalidArgumentException
{
}
