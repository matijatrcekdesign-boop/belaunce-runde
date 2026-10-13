<?php
/**
 * @package     Belaunce.Administrator
 * @subpackage  com_belauncerunde
 *
 * @copyright   (C) 2026 Belaunce
 * @license     GNU General Public License version 2 or later
 */

namespace Belaunce\Component\Belauncerunde\Administrator\Service;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/**
 * Adapter za branje članstva iz AcyMailinga.
 *
 * Razred se zanaša na preverjeno shemo AcyMailing Enterprise 11.1.1:
 * `#__acym_user` (`id`, `name`, `email`, `active`, `cms_id`),
 * `#__acym_user_has_list` (`user_id`, `list_id`, `status`) in
 * `#__acym_list` (`id`, `name`, `active`). Adapter tabele AcyMailinga
 * vedno samo bere in nikoli ne izvaja zapisovalnih poizvedb.
 *
 * @since  0.3.0
 */
class AcymailingAdapter
{
    private const TABELA_UPORABNIKI = '#__acym_user';
    private const TABELA_NAROCNINE  = '#__acym_user_has_list';
    private const TABELA_LISTE      = '#__acym_list';

    /** @var DatabaseInterface Povezava do baze. */
    private DatabaseInterface $db;

    /** @var bool|null Predpomnjen rezultat preverjanja namestitve. */
    private ?bool $namescen = null;

    /**
     * Pripravi adapter.
     *
     * @param   DatabaseInterface|null  $db  Povezava do baze.
     *
     * @since   0.3.0
     */
    public function __construct(?DatabaseInterface $db = null)
    {
        $this->db = $db ?: Factory::getContainer()->get(DatabaseInterface::class);
    }

    /**
     * Preveri, ali obstajajo vse AcyMailing tabele, ki jih beremo.
     *
     * @return  bool
     *
     * @since   0.3.0
     */
    public function jeNamescen(): bool
    {
        if ($this->namescen !== null) {
            return $this->namescen;
        }

        try {
            $tabele = $this->db->getTableList();
            $potrebne = [
                $this->db->replacePrefix(self::TABELA_UPORABNIKI),
                $this->db->replacePrefix(self::TABELA_NAROCNINE),
                $this->db->replacePrefix(self::TABELA_LISTE),
            ];

            foreach ($potrebne as $tabela) {
                if (!\in_array($tabela, $tabele, true)) {
                    return $this->namescen = false;
                }
            }

            return $this->namescen = true;
        } catch (\Throwable $e) {
            $this->zabeleziNapako('Preverjanje namestitve AcyMailinga ni uspelo.', $e);

            return $this->namescen = false;
        }
    }

    /**
     * Vrne vse AcyMailing liste po imenu.
     *
     * @return  array<int, string>
     *
     * @since   0.3.0
     */
    public function seznamList(): array
    {
        if (!$this->jeNamescen()) {
            return [];
        }

        try {
            $query = $this->db->createQuery()
                ->select($this->db->quoteName(['id', 'name']))
                ->from($this->db->quoteName(self::TABELA_LISTE))
                ->order($this->db->quoteName('name') . ' ASC, ' . $this->db->quoteName('id') . ' ASC');
            $this->db->setQuery($query);

            $liste = [];

            foreach ($this->db->loadObjectList() as $row) {
                $liste[(int) $row->id] = (string) $row->name;
            }

            return $liste;
        } catch (\Throwable $e) {
            $this->zabeleziNapako('Branje AcyMailing list ni uspelo.', $e);

            return [];
        }
    }

    /**
     * Poišče aktivnega člana na izbrani listi.
     *
     * @param   string  $email    Email za preverjanje.
     * @param   int     $listaId  ID liste članov.
     *
     * @return  object|null
     *
     * @since   0.3.0
     */
    public function poisciClana(string $email, int $listaId): ?object
    {
        $email = $this->normalizirajEmail($email);

        if ($listaId <= 0 || !$this->jeNamescen() || !$this->jeVeljavenEmail($email)) {
            return null;
        }

        try {
            $aktiven = 1;
            $status  = 1;
            $query = $this->db->createQuery()
                ->select(
                    [
                        $this->db->quoteName('u.id', 'acym_id'),
                        $this->db->quoteName('u.email'),
                        $this->db->quoteName('u.name', 'ime'),
                        $this->db->quoteName('u.cms_id'),
                    ]
                )
                ->from($this->db->quoteName(self::TABELA_UPORABNIKI, 'u'))
                ->join('INNER', $this->db->quoteName(self::TABELA_NAROCNINE, 'l') . ' ON ' . $this->db->quoteName('l.user_id') . ' = ' . $this->db->quoteName('u.id'))
                ->where('LOWER(' . $this->db->quoteName('u.email') . ') = :email')
                ->where($this->db->quoteName('u.active') . ' = :aktiven')
                ->where($this->db->quoteName('l.list_id') . ' = :lista_id')
                ->where($this->db->quoteName('l.status') . ' = :status')
                ->bind(':email', $email)
                ->bind(':aktiven', $aktiven, ParameterType::INTEGER)
                ->bind(':lista_id', $listaId, ParameterType::INTEGER)
                ->bind(':status', $status, ParameterType::INTEGER);
            $this->db->setQuery($query);
            $clan = $this->db->loadObject();

            return $clan ?: null;
        } catch (\Throwable $e) {
            $this->zabeleziNapako('Iskanje AcyMailing člana ni uspelo.', $e);

            return null;
        }
    }

    /**
     * Vrne diagnostično kodo za email in listo.
     *
     * @param   string  $email    Email za preverjanje.
     * @param   int     $listaId  ID liste članov.
     *
     * @return  string
     *
     * @since   0.3.0
     */
    public function preveriEmail(string $email, int $listaId): string
    {
        $email = $this->normalizirajEmail($email);

        if ($listaId <= 0) {
            return 'LISTA_NI_NASTAVLJENA';
        }

        if (!$this->jeVeljavenEmail($email)) {
            return 'NEVELJAVEN_EMAIL';
        }

        if (!$this->jeNamescen()) {
            return 'ACYM_NI_NAMESCEN';
        }

        try {
            $query = $this->db->createQuery()
                ->select(
                    [
                        $this->db->quoteName('u.id'),
                        $this->db->quoteName('u.active'),
                        $this->db->quoteName('l.status', 'status_liste'),
                    ]
                )
                ->from($this->db->quoteName(self::TABELA_UPORABNIKI, 'u'))
                ->join(
                    'LEFT',
                    $this->db->quoteName(self::TABELA_NAROCNINE, 'l')
                    . ' ON ' . $this->db->quoteName('l.user_id') . ' = ' . $this->db->quoteName('u.id')
                    . ' AND ' . $this->db->quoteName('l.list_id') . ' = :lista_id'
                )
                ->where('LOWER(' . $this->db->quoteName('u.email') . ') = :email')
                ->bind(':lista_id', $listaId, ParameterType::INTEGER)
                ->bind(':email', $email);
            $this->db->setQuery($query);
            $row = $this->db->loadObject();

            if (!$row) {
                return 'NI_NAROCNIK';
            }

            if ((int) $row->active !== 1) {
                return 'NEAKTIVEN';
            }

            if ($row->status_liste === null) {
                return 'NI_NA_LISTI';
            }

            if ((int) $row->status_liste !== 1) {
                return 'ODJAVLJEN';
            }

            return 'CLAN';
        } catch (\Throwable $e) {
            $this->zabeleziNapako('Diagnostika AcyMailing emaila ni uspela.', $e);

            return 'NI_NAROCNIK';
        }
    }

    /**
     * Preveri, ali je znani AcyMailing uporabnik trenutni član liste.
     *
     * @param   int  $acymId   ID AcyMailing uporabnika.
     * @param   int  $listaId  ID liste članov.
     *
     * @return  bool
     *
     * @since   0.3.0
     */
    public function jeClan(int $acymId, int $listaId): bool
    {
        if ($acymId <= 0 || $listaId <= 0 || !$this->jeNamescen()) {
            return false;
        }

        try {
            $aktiven = 1;
            $status  = 1;
            $query = $this->db->createQuery()
                ->select('COUNT(*)')
                ->from($this->db->quoteName(self::TABELA_UPORABNIKI, 'u'))
                ->join('INNER', $this->db->quoteName(self::TABELA_NAROCNINE, 'l') . ' ON ' . $this->db->quoteName('l.user_id') . ' = ' . $this->db->quoteName('u.id'))
                ->where($this->db->quoteName('u.id') . ' = :acym_id')
                ->where($this->db->quoteName('u.active') . ' = :aktiven')
                ->where($this->db->quoteName('l.list_id') . ' = :lista_id')
                ->where($this->db->quoteName('l.status') . ' = :status')
                ->bind(':acym_id', $acymId, ParameterType::INTEGER)
                ->bind(':aktiven', $aktiven, ParameterType::INTEGER)
                ->bind(':lista_id', $listaId, ParameterType::INTEGER)
                ->bind(':status', $status, ParameterType::INTEGER);
            $this->db->setQuery($query);

            return (int) $this->db->loadResult() > 0;
        } catch (\Throwable $e) {
            $this->zabeleziNapako('Preverjanje AcyMailing člana po ID ni uspelo.', $e);

            return false;
        }
    }

    /**
     * Poišče naročnika po AcyMailing ID brez preverjanja liste.
     *
     * @param   int  $acymId  ID AcyMailing uporabnika.
     *
     * @return  object|null
     *
     * @since   0.3.0
     */
    public function poisciPoId(int $acymId): ?object
    {
        if ($acymId <= 0 || !$this->jeNamescen()) {
            return null;
        }

        try {
            $query = $this->db->createQuery()
                ->select(
                    [
                        $this->db->quoteName('id', 'acym_id'),
                        $this->db->quoteName('email'),
                        $this->db->quoteName('name', 'ime'),
                        $this->db->quoteName('active'),
                        $this->db->quoteName('cms_id'),
                    ]
                )
                ->from($this->db->quoteName(self::TABELA_UPORABNIKI))
                ->where($this->db->quoteName('id') . ' = :acym_id')
                ->bind(':acym_id', $acymId, ParameterType::INTEGER);
            $this->db->setQuery($query);
            $narocnik = $this->db->loadObject();

            return $narocnik ?: null;
        } catch (\Throwable $e) {
            $this->zabeleziNapako('Iskanje AcyMailing naročnika po ID ni uspelo.', $e);

            return null;
        }
    }

    /**
     * Vrne ID-je vseh trenutnih članov izbrane liste.
     *
     * @param   int  $listaId  ID liste članov.
     *
     * @return  int[]
     *
     * @since   0.3.0
     */
    public function idjiClanov(int $listaId): array
    {
        if ($listaId <= 0 || !$this->jeNamescen()) {
            return [];
        }

        try {
            $aktiven = 1;
            $status  = 1;
            $query = $this->db->createQuery()
                ->select($this->db->quoteName('u.id'))
                ->from($this->db->quoteName(self::TABELA_UPORABNIKI, 'u'))
                ->join('INNER', $this->db->quoteName(self::TABELA_NAROCNINE, 'l') . ' ON ' . $this->db->quoteName('l.user_id') . ' = ' . $this->db->quoteName('u.id'))
                ->where($this->db->quoteName('u.active') . ' = :aktiven')
                ->where($this->db->quoteName('l.list_id') . ' = :lista_id')
                ->where($this->db->quoteName('l.status') . ' = :status')
                ->order($this->db->quoteName('u.id') . ' ASC')
                ->bind(':aktiven', $aktiven, ParameterType::INTEGER)
                ->bind(':lista_id', $listaId, ParameterType::INTEGER)
                ->bind(':status', $status, ParameterType::INTEGER);
            $this->db->setQuery($query);

            return array_map('intval', $this->db->loadColumn());
        } catch (\Throwable $e) {
            $this->zabeleziNapako('Branje ID-jev AcyMailing članov ni uspelo.', $e);

            return [];
        }
    }

    /**
     * Normalizira email za primerjavo v bazi.
     *
     * @param   string  $email  Vhodni email.
     *
     * @return  string
     *
     * @since   0.3.0
     */
    private function normalizirajEmail(string $email): string
    {
        $email = trim($email);

        return \function_exists('mb_strtolower') ? mb_strtolower($email) : strtolower($email);
    }

    /**
     * Preveri osnovno veljavnost emaila.
     *
     * @param   string  $email  Normaliziran email.
     *
     * @return  bool
     *
     * @since   0.3.0
     */
    private function jeVeljavenEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Zapiše tehnično napako brez osebnih podatkov in SQL izpisa uporabniku.
     *
     * @param   string      $sporocilo  Varno sporočilo za dnevnik.
     * @param   \Throwable  $e          Izjema.
     *
     * @return  void
     *
     * @since   0.3.0
     */
    private function zabeleziNapako(string $sporocilo, \Throwable $e): void
    {
        Log::add($sporocilo . ' Vrsta napake: ' . \get_class($e) . '.', Log::ERROR, 'com_belauncerunde');
    }
}
