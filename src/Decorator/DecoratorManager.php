<?php
/**
 * 06.09.2018 20:42 Vacheslav Silyutin <diversantvlz@gmail.com>
 */

namespace src\Decorator;

use DateTime;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use src\Integration\DataProvider;

class DecoratorManager extends DataProvider
{
    private $user;
    private $host;
    protected $cache;
    protected $logger;

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     * @param CacheItemPoolInterface $cache
     */
    public function __construct($host, $user, $password, CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        parent::__construct($host, $user, $password);
        $this->user = $user;
        $this->host = $host;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse(array $input)
    {
        try {
            $cacheKey = $this->getCacheKey($input);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            $result = parent::get($input);

            $cacheItem
                ->set($result)
                ->expiresAt(new DateTime('+1 day'));

            return $result;
        } catch (Exception $e) {
            $this->logger->critical($e->__toString());
        }

        return [];
    }

    public function getCacheKey(array $input)
    {
        return md5($this->user . $this->host . json_encode($input));
    }
}
