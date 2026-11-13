<?php

namespace Belaunce\Plugin\Task\Belauncerunde\Extension;

use Belaunce\Component\Belauncerunde\Administrator\Service\PushService;
use Belaunce\Component\Belauncerunde\Administrator\Service\VzdrzevanjeService;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;

\defined('_JEXEC') or die;

/** Schedulerju ponudi dnevno vzdrževanje rund in pogostejši opomnik pred štartom (push). */
final class Belauncerunde extends CMSPlugin implements SubscriberInterface
{
    use TaskPluginTrait;

    /** @var array<string, array<string, string>> Vrsti opravil tega vtičnika. */
    protected const TASKS_MAP = [
        'belaunce_vzdrzevanje' => [
            'langConstPrefix' => 'PLG_TASK_BELAUNCERUNDE_VZDRZEVANJE',
            'method' => 'opraviVzdrzevanje',
        ],
        // Ločeno od vzdrževanja, ker teče pogosteje (npr. vsakih 15 minut).
        'belaunce_opomnik' => [
            'langConstPrefix' => 'PLG_TASK_BELAUNCERUNDE_OPOMNIK',
            'method' => 'opraviOpomnik',
        ],
    ];

    /** @var bool Registracija dnevnika v eni izvedbi rutine. */
    private bool $dnevnikRegistriran = false;

    /**
     * Vrne dogodka za prikaz opravila in njegov zagon.
     *
     * @return  array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList' => 'advertiseRoutines',
            'onExecuteTask' => 'standardRoutineHandler',
        ];
    }

    /**
     * Izvede neodvisne dele; ob napaki posameznega dela nadaljuje z naslednjim.
     *
     * @param  ExecuteTaskEvent  $event
     * @return  int
     */
    protected function opraviVzdrzevanje(ExecuteTaskEvent $event): int
    {
        try {
            $this->pripraviDnevnik();
            Log::add('VZDRZEVANJE_START', Log::INFO, 'com_belauncerunde');
            $servis = new VzdrzevanjeService();

            foreach (['sinhronizacijaClanov', 'izbrisGostov', 'ciscenje', 'ciscenjePush'] as $del) {
                try {
                    $servis->{$del}();
                } catch (\Throwable $napaka) {
                    // Besedilo izjeme lahko vsebuje osebne podatke ali SQL, zato zapišemo le kodo dela.
                    Log::add('VZDRZEVANJE_DEL_NAPAKA del=' . $del, Log::ERROR, 'com_belauncerunde');
                }
            }

            Log::add('VZDRZEVANJE_KONEC', Log::INFO, 'com_belauncerunde');

            return Status::OK;
        } catch (\Throwable $napaka) {
            return Status::KNOCKOUT;
        }
    }

    /**
     * Pošlje push opomnike za runde, ki štartajo v nastavljenem oknu (push_opomnik_ur).
     * Napaka pošiljanja nikoli ne ustavi Schedulerja; dnevnik beleži le kode in števila.
     *
     * @param  ExecuteTaskEvent  $event
     * @return  int
     */
    protected function opraviOpomnik(ExecuteTaskEvent $event): int
    {
        try {
            $this->pripraviDnevnik();
            $rund = PushService::posljiOpomnike();

            if ($rund > 0) {
                Log::add('PUSH_OPOMNIK rund=' . $rund, Log::INFO, 'com_belauncerunde');
            }

            return Status::OK;
        } catch (\Throwable $napaka) {
            return Status::KNOCKOUT;
        }
    }

    /** Vtičnik deluje zunaj zagona komponente, zato si dnevnik zagotovi sam. */
    private function pripraviDnevnik(): void
    {
        if ($this->dnevnikRegistriran) {
            return;
        }

        Log::addLogger([
            'text_file' => 'com_belauncerunde.php',
            'text_entry_format' => "{DATETIME}\t{PRIORITY}\t{CATEGORY}\t{MESSAGE}",
        ], Log::ALL, ['com_belauncerunde']);
        $this->dnevnikRegistriran = true;
    }
}
