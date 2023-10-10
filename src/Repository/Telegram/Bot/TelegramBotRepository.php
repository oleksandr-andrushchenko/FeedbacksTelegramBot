<?php

declare(strict_types=1);

namespace App\Repository\Telegram\Bot;

use App\Entity\Telegram\TelegramBot;
use App\Enum\Telegram\TelegramBotGroupName;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TelegramBot>
 *
 * @method TelegramBot|null find($id, $lockMode = null, $lockVersion = null)
 * @method TelegramBot|null findOneBy(array $criteria, array $orderBy = null)
 * @method TelegramBot[]    findAll()
 * @method TelegramBot[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TelegramBotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelegramBot::class);
    }

    public function findAnyOneByUsername(string $username): ?TelegramBot
    {
        return $this->findOneBy([
            'username' => $username,
        ]);
    }

    public function findOneByUsername(string $username): ?TelegramBot
    {
        return $this->findOneBy([
            'username' => $username,
            'deletedAt' => null,
        ]);
    }

    /**
     * @param TelegramBotGroupName $group
     * @return TelegramBot[]
     */
    public function findByGroup(TelegramBotGroupName $group): array
    {
        return $this->findBy([
            'group' => $group,
            'deletedAt' => null,
        ]);
    }

    /**
     * @param TelegramBotGroupName $group
     * @return TelegramBot[]
     */
    public function findPrimaryByGroup(TelegramBotGroupName $group): array
    {
        return $this->findBy([
            'group' => $group,
            'primary' => true,
            'deletedAt' => null,
        ]);
    }

    /**
     * @param TelegramBotGroupName $group
     * @param string $countryCode
     * @return TelegramBot[]
     */
    public function findByGroupAndCountry(TelegramBotGroupName $group, string $countryCode): array
    {
        return $this->findBy([
            'group' => $group,
            'countryCode' => $countryCode,
            'deletedAt' => null,
        ]);
    }

    public function findOnePrimaryByBot(TelegramBot $bot): ?TelegramBot
    {
        return $this->findOneBy([
            'group' => $bot->getGroup(),
            'countryCode' => $bot->getCountryCode(),
            'localeCode' => $bot->getLocaleCode(),
            'primary' => true,
            'deletedAt' => null,
        ]);
    }
}
