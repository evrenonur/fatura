<?php

declare(strict_types=1);

namespace Mlevent\Fatura;

use Mlevent\Fatura\Exceptions\ApiException;
use Mlevent\Fatura\Exceptions\BadResponseException;

class Client
{
    /**
     * @var array response
     */
    protected array $response = [];

    /**
     * @var array headers
     */
    protected static $headers = [
        'content-type' => 'application/x-www-form-urlencoded',
    ];

    /**
     * @var array proxy configuration
     */
    protected static $proxy = [];
    
    /**
     * setProxy
     * 
     * @param string $proxy Proxy URL (örn: "http://username:password@proxy.example.com:8080")
     * @return void
     */
    public static function setProxy(string $proxy): void
    {
        // URL'den kullanıcı adı ve şifreyi ayıkla
        $parsedUrl = parse_url($proxy);
        
        if (!isset($parsedUrl['scheme'])) {
            $parsedUrl['scheme'] = 'http';
        }

        if (isset($parsedUrl['user']) && isset($parsedUrl['pass'])) {
            self::$proxy = [
                'proxy' => "{$parsedUrl['scheme']}://{$parsedUrl['user']}:{$parsedUrl['pass']}@{$parsedUrl['host']}:{$parsedUrl['port']}"
            ];
        } else {
            self::$proxy = [
                'proxy' => $proxy
            ];
        }
    }

    /**
     * clearProxy
     * 
     * @return void
     */
    public static function clearProxy(): void
    {
        self::$proxy = [];
    }
    
    /**
     * request
     *
     * @param string     $url
     * @param array|null $parameters
     * @param boolean    $post
     */
    public function __construct(string $url, ?array $parameters = null, bool $post = true)
    {
        try {
            $config = [
                'verify' => false,
            ];

            // Proxy ayarlarını config'e ekle
            if (!empty(self::$proxy)) {
                $config = array_merge($config, self::$proxy);
            }

          
            $client = new \GuzzleHttp\Client($config);

            $options = [
                'headers'     => self::$headers,
                'form_params' => $parameters,
            ];

            $request = $client->request($post ? 'POST' : 'GET', $url, $options);
            
            if ($response = json_decode($request->getBody()->getContents(), true)) {
                if (is_array($response)) {
                    $this->response = $response;
                }
            }

            if (!$this->response || isset($this->response['error']) || !empty($this->response['data']['hata'])) {
                throw new ApiException('İstek başarısız oldu.', $parameters, $this->response, $request->getStatusCode());
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            throw new BadResponseException($e->getMessage(), $parameters, null, $e->getCode());
        }
    }

    /**
     * get
     *
     * @param  string|null $element
     * @return mixed
     */
    public function get(?string $element = null): mixed
    {
        return is_null($element) 
            ? $this->response
            : $this->response[$element];
    }

    /**
     * object
     *
     * @param  string|null $element
     * @return mixed
     */
    public function object(?string $element = null): mixed
    {
        $response = json_decode(json_encode($this->response, JSON_FORCE_OBJECT), false);
        
        return is_null($element) 
            ? $response
            : $response->$element;
    }
}