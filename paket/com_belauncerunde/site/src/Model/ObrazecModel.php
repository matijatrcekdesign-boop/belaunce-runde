<?php

namespace Belaunce\Component\Belauncerunde\Site\Model;

use Belaunce\Component\Belauncerunde\Administrator\Helper\CasHelper;
use Belaunce\Component\Belauncerunde\Site\Helper\TrasaHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\FormModel;
use Joomla\CMS\User\User;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

/** Frontend vnos uporablja lastna dovoljena polja in obstoječo administratorsko tabelo. */
class ObrazecModel extends FormModel
{
    /** @var array Edina polja, ki jih sme posredovati član; ID je ločen POST podatek. */
    private const POLJA = ['naslov', 'tip_id', 'tezavnost_id', 'zacetek', 'lokacija',
        'dolzina_km', 'trajanje_min', 'opombe', 'trasa_url', 'odprto_za_goste'];

    /** Naloži samo site XML in samo objavljene možnosti šifrantov po ordering. */
    public function getForm($data = [], $loadData = true)
    {
        $obrazec = $this->loadForm('com_belauncerunde.obrazec', 'runda', ['control' => 'jform', 'load_data' => $loadData]);

        if ($obrazec !== false) {
            foreach (['tip_id' => '#__belaunce_tipi', 'tezavnost_id' => '#__belaunce_tezavnosti'] as $polje => $tabela) {
                $baza = $this->getDatabase();
                $poizvedba = $baza->createQuery()->select($baza->quoteName(['id', 'naziv']))
                    ->from($baza->quoteName($tabela))->where('stanje = 1')->order('ordering ASC, id ASC');

                foreach ($baza->setQuery($poizvedba)->loadObjectList() as $moznost) {
                    // addOption dodaja besedilo v SimpleXML, zato ampersand potrebuje XML zapis.
                    $obrazec->getField($polje)->addOption(htmlspecialchars($moznost->naziv, ENT_XML1, 'UTF-8'), ['value' => $moznost->id]);
                }
            }
        }

        return $obrazec;
    }

    /** Pravico vsakokrat določi iz identitete in baze, z mejo štarta po UTC uri baze. */
    public function dovoljenje(int $id, User $uporabnik): string
    {
        if ($id === 0) {
            $prijave = $this->getMVCFactory()->createModel('Prijave', 'Site', []);

            return $prijave->jeClan($uporabnik) ? '' : 'ni_clan';
        }

        $runda = $this->preberiRundo($id);

        if ($runda === null) {
            return 'ni_najdena';
        }

        if ($uporabnik->guest || (int) $runda->vodja_id !== (int) $uporabnik->id) {
            return 'prepovedano';
        }

        return (int) $runda->stanje === 1 && (bool) $runda->pred_startom ? '' : 'zaklenjena';
    }

    /** Hrani sejo ločeno za vsak obrazec, da napaka ne napolni druge runde. */
    public function kljucSeje(int $id): string
    {
        return 'com_belauncerunde.obrazec.' . $id . '.data';
    }

    /** Podatke iz baze pretvori v lokalni čas samo ob prvem nalaganju, nikoli iz seje. */
    protected function loadFormData()
    {
        $app = Factory::getApplication();
        $id = $app->getInput()->getUint('id', 0);

        if ($this->dovoljenje($id, $app->getIdentity()) !== '') {
            return [];
        }

        $podatki = $app->getUserState($this->kljucSeje($id), []);

        if ($podatki !== []) {
            return $podatki;
        }

        if ($id === 0) {
            return ['lokacija' => ComponentHelper::getParams('com_belauncerunde')->get('privzeta_lokacija', 'Gorenja vas'),
                'zacetek' => '', 'odprto_za_goste' => 0];
        }

        $runda = $this->preberiRundo($id);
        $podatki = array_intersect_key((array) $runda, array_flip(self::POLJA));
        $podatki['zacetek'] = CasHelper::izUtc($runda->zacetek);

        return $podatki;
    }

    /** Omeji sejo in zapis na dovoljena skalarna polja, brez lastništva iz zahtevka. */
    public function podatkiObrazca(array $podatki): array
    {
        $podatki = array_intersect_key($podatki, array_flip(self::POLJA));

        foreach ($podatki as $polje => $vrednost) {
            $podatki[$polje] = is_scalar($vrednost) ? $vrednost : '';
        }

        return $podatki;
    }

    /** Validira site obrazec in meje neobveznega trajanja ter shrani prek RundaTable. */
    public function shrani(int $id, array $vnos, User $uporabnik): array
    {
        $dovoljenje = $this->dovoljenje($id, $uporabnik);

        if ($dovoljenje !== '') {
            return ['stanje' => $dovoljenje, 'id' => $id];
        }

        $podatki = $this->validate($this->getForm([], false), $this->podatkiObrazca($vnos));

        if ($podatki === false) {
            return ['stanje' => 'neveljavno', 'id' => $id];
        }

        $trajanje = trim((string) ($podatki['trajanje_min'] ?? ''));
        $trasa = trim((string) ($podatki['trasa_url'] ?? ''));

        if (($trajanje !== '' && (!ctype_digit($trajanje) || (int) $trajanje < 1 || (int) $trajanje > 1440))
            || ($trasa !== '' && (!TrasaHelper::preveriUrl($trasa) || strlen($trasa) > 2048))
            || !\in_array((int) ($podatki['odprto_za_goste'] ?? 0), [0, 1], true)
            || !$this->objavljenSifrant('#__belaunce_tipi', (int) ($podatki['tip_id'] ?? 0))
            || !$this->objavljenSifrant('#__belaunce_tezavnosti', (int) ($podatki['tezavnost_id'] ?? 0))) {
            $this->setError(Text::_('COM_BELAUNCERUNDE_OBRAZEC_NEVELJAVNO'));

            return ['stanje' => 'neveljavno', 'id' => $id];
        }

        // Calendar ne uporablja Joomlinega časovnega pasu. Stroga oblika in povratna
        // pretvorba zavrneta neveljavne datume ter neobstoječo uro ob prehodu na poletni čas.
        $lokalni = trim((string) ($podatki['zacetek'] ?? ''));

        try {
            if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $lokalni)) {
                throw new \InvalidArgumentException();
            }

            $podatki['zacetek'] = CasHelper::vUtc($lokalni);

            if (CasHelper::izUtc($podatki['zacetek']) !== $lokalni) {
                throw new \InvalidArgumentException();
            }
        } catch (\Exception $napaka) {
            $this->setError(Text::_('COM_BELAUNCERUNDE_OBRAZEC_NAPAKA_CAS'));

            return ['stanje' => 'neveljavno', 'id' => $id];
        }

        $podatki['trajanje_min'] = $trajanje === '' ? null : (int) $trajanje;
        $podatki['trasa_url'] = $trasa === '' ? null : $trasa;
        $podatki['opombe'] = trim((string) ($podatki['opombe'] ?? '')) === '' ? null : $podatki['opombe'];
        $tabela = $this->getMVCFactory()->createTable('Runda', 'Administrator', []);

        if ($id > 0 && !$tabela->load($id)) {
            return ['stanje' => 'ni_najdena', 'id' => $id];
        }

        if ($id === 0) {
            $podatki['vodja_id'] = (int) $uporabnik->id;
            $podatki['stanje'] = 1;
            $podatki['ustvaril_id'] = (int) $uporabnik->id;
            $podatki['ustvarjeno'] = CasHelper::zdajUtc();
        }

        // Pri urejanju ostanejo lastništvo, stanje in prvotni revizijski podatki iz tabele.
        if (!$tabela->bind($podatki) || !$tabela->check()) {
            $this->setError(Text::_('COM_BELAUNCERUNDE_OBRAZEC_NEVELJAVNO'));

            return ['stanje' => 'neveljavno', 'id' => $id];
        }

        $dovoljenje = $this->dovoljenje($id, $uporabnik);

        if ($dovoljenje !== '') {
            return ['stanje' => $dovoljenje, 'id' => $id];
        }

        if (!$tabela->store()) {
            return ['stanje' => 'napaka_zapisa', 'id' => $id];
        }

        return ['stanje' => $id === 0 ? 'ustvarjena' : 'shranjena', 'id' => (int) $tabela->id];
    }

    /** Odpove samo lastno objavljeno rundo pred štartom; prijav se ne dotika. */
    public function odpovej(int $id, User $uporabnik): string
    {
        if ($id <= 0) {
            return 'ni_najdena';
        }

        $dovoljenje = $this->dovoljenje($id, $uporabnik);

        if ($dovoljenje !== '') {
            return $dovoljenje;
        }

        $tabela = $this->getMVCFactory()->createTable('Runda', 'Administrator', []);

        if (!$tabela->load($id)) {
            return 'ni_najdena';
        }

        $tabela->stanje = 2;

        if (!$tabela->check()) {
            return 'napaka_zapisa';
        }

        $dovoljenje = $this->dovoljenje($id, $uporabnik);

        return $dovoljenje !== '' ? $dovoljenje : ($tabela->store() ? 'odpovedana' : 'napaka_zapisa');
    }

    /** Bere stanje za pravice in podatke lastnega obrazca brez kontaktov uporabnika. */
    private function preberiRundo(int $id): ?object
    {
        $baza = $this->getDatabase();
        $poizvedba = $baza->createQuery()->select('*')->select('zacetek > UTC_TIMESTAMP() AS pred_startom')
            ->from($baza->quoteName('#__belaunce_runde'))->where('id = :id')
            ->bind(':id', $id, ParameterType::INTEGER);

        return $baza->setQuery($poizvedba)->loadObject() ?: null;
    }

    /** Preveri, da je ponarejeni ID šifranta še vedno objavljen. */
    private function objavljenSifrant(string $tabela, int $id): bool
    {
        $baza = $this->getDatabase();
        $poizvedba = $baza->createQuery()->select('COUNT(*)')->from($baza->quoteName($tabela))
            ->where('id = :id')->where('stanje = 1')->bind(':id', $id, ParameterType::INTEGER);

        return (int) $baza->setQuery($poizvedba)->loadResult() === 1;
    }
}
