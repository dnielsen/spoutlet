<?php

namespace Platformd\SpoutletBundle\Command;

use Platformd\SpoutletBundle\Command\BaseCommand,
    Platformd\SpoutletBundle\QueueMessage\MassEmailQueueMessage,
    Platformd\SpoutletBundle\Entity\ScriptLastRun
;

use
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface
;

use DateTime;

class EDialogOptInCommand extends BaseCommand
{
    const SCRIPT_ID = 'e-dialog_dell_opt_in';

    private $edlg = array('ad','ai','ar','bb','bm','bo','br','bs','bz','ca','cl','co','cr','dm','do','ec','gd','gt','gy','hn','ht','jm','kn','hy','lc','mq','mx','ni','pa','pe','pr','py','sr','sv','tt','us','uy','vc','ve','vg','vi','ag','aw','tc');
    private $edlgapj = array('au','cn','hk','id','in','jp','kr','my','nz','ph','sg','tw');
    private $edlge = array('at','be','ch','de','dk','es','fr','ie','it','nl','no','pl','pt','se','uk');

    private $countryLocaleMap;

    protected function configure()
    {
        $this
            ->setName('pd:optin:eDialogUpload')
            ->setDescription('Uploads Dell Opt In data to E Dialog.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command uploads Dell opt in data to E Dialog for users created since last run.

  <info>php %command.full_name%</info>
EOT
            );
    }

    protected function shred($filename)
    {
        if (file_exists($filename)) {
            exec('shred -uv '.$filename.' > /dev/null 2>&1');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->stdOutput   = $output;
        $container         = $this->getContainer();
        $em                = $container->get('doctrine')->getEntityManager();
        $scriptLastRunRepo = $em->getRepository('SpoutletBundle:ScriptLastRun');
        $countryRepo       = $em->getRepository('SpoutletBundle:Country');

        $this->countryLocaleMap = $countryRepo->getCountryCodeLocaleArray();

        $dateTime          = new DateTime();

        $this->output();
        $this->output(0, 'PlatformD E-Dialog Dell Opt In Upload');

        $hasRun = $scriptLastRunRepo->find(self::SCRIPT_ID);

        if (!$hasRun) {
            $hasRun = new ScriptLastRun(self::SCRIPT_ID);
            $em->persist($hasRun);
            $em->flush();
        }

        $lastIdProcessed = $hasRun->getLastId();

        if ($lastIdProcessed) {
            $this->output();
            $this->output(2, 'Getting opted in users with ID > "'.$lastIdProcessed.'"');
        } else {
            $this->output();
            $this->output(2, 'Getting all opted in users.');
        }

        $qb = $em->createQueryBuilder()
            ->select('count(u.id)')
            ->from('UserBundle:User', 'u')
            ->andWhere('u.subscribedGamingNews = true');

        if ($lastIdProcessed) {
            $qb->andWhere('u.id > :id')
                ->setParameter('id', $lastIdProcessed);
        }

        $userCount = (int) $qb->getQuery()->getSingleScalarResult();

        if ($userCount < 1) {
            $this->output();
            $this->output(2, 'No users since last run.');
            $this->output();
            exit;
        }

        $qb = $em->createQueryBuilder()
            ->select('u')
            ->from('UserBundle:User', 'u')
            ->andWhere('u.subscribedGamingNews = true');

        if ($lastIdProcessed) {
            $qb->andWhere('u.id > :id')
                ->setParameter('id', $lastIdProcessed);
        }

        $iterableResult = $qb->getQuery()->iterate();

        $this->output(2, 'Processing "'.$userCount.'" users.');

        $filename = 'DELLUS_AW_ARENA_WELCOME_'.$dateTime->format('YmdHis').'.dat';
        $lastId   = 0;

        if (($handle = fopen($filename, 'w')) !== FALSE) {

            foreach ($iterableResult as $row) {
                $user = $row[0];

                $userCountry = strtolower($user->getCountry());

                // E-Dialog use "UK" whereas we (and ISO) use "GB"
                if ($userCountry == 'gb') {
                    $userCountry = 'uk';
                }

                if (in_array($userCountry, $this->edlg)) {
                    $sourceCode = 'edlg';
                } else if (in_array($userCountry, $this->edlgapj)) {
                    $sourceCode = 'edlgapj';
                } else if (in_array($userCountry, $this->edlge)) {
                    $sourceCode = 'edlge';
                } else {
                    $sourceCode = 'edlg';
                }

                $dataRow = $this->getDataRow($user, $sourceCode, $userCountry);

                fwrite($handle, $dataRow);

                $lastId = $user->getId();
            }

        } else {
            $this->error('Unable to open file "'.$filename.'" for writing.');
        }

        fclose($handle);

        $this->encryptAndUploadData($filename);

        $hasRun->setLastRun($dateTime);
        $hasRun->setLastId($lastId);
        $em->persist($hasRun);
        $em->flush();

        $this->output();
        $this->output(2, 'Done.');

        $this->outputErrors();

        $this->output(0);
    }

    protected function encryptAndUploadData($filename)
    {
        if (!file_exists($filename)) {
            $this->error('File "'.$filename.'" does not exist.');
            exit;
        }

        $recipients      = array();
        $recipientString = '';
        $configFile      = '/home/'.trim(`whoami`).'/scripts/e-dialog/config.php';

        if (!file_exists($configFile)) {
            $this->shred($filename);
            $this->error('No config file found at "'.$configFile.'".');
            exit;
        }

        require($configFile);

        foreach ($recipients as $recipient) {
            $recipientString .= ' -r "'.$recipient.'"';
        }

        if ($recipientString == '') {
            $this->error('No GPG recipients found.');
            exit;
        }

        $this->output(2, 'Encrypting data file.');

        exec('gpg -e --batch --always-trust'.$recipientString.' '.$filename);

        if (!file_exists($filename.'.gpg')) {
            $this->error('Unable to generate encrypted data file.');
            exit;
        }

        $this->output(2, 'Shredding unencrypted file "'.$filename.'".');
        $this->shred($filename);

        $filename = $filename.'.gpg';

        $ftpInfo = array($ftpHost, $ftpUser, $ftpPass, $ftpDirectory);

        $this->output(2, 'Uploading encrypted data file to FTP server.');

        exec('ftp-upload -h '.$ftpInfo[0].' -u '.$ftpInfo[1].' --password '.$ftpInfo[2].' --passive -d "'.$ftpInfo[3].'" '.$filename);
        $this->output(2, 'Shredding encrypted file "'.$filename.'".');
        $this->shred($filename);
    }

    protected function getDataRow($user, $sourceCode, $userCountry)
    {
        $created  = $user->getCreated() ? $user->getCreated()->format('YmdHis') : null;
        $fullName = $user->getFirstName() . ' ' . $user->getLastName();
        $country  = ($userCountry && strlen($userCountry) == 2) ? $userCountry : null;
        $locale   = isset($this->countryLocaleMap[$country]) ? $this->countryLocaleMap[$country] : null;
        $language = $locale == 'es' || $locale == 'ja' ? $locale : 'en';
        $sysOwner = $user->getHasAlienwareSystem() ? 'Y' : 'N';

        $data     = array();

        $data[0]  = $user->getEmail();
        $data[1]  = $sourceCode;                    // SOURCE_CODE
        $data[2]  = null;                           // BUS+UNIT_ID
        $data[3]  = 'Y';                            // EMAILABLE_FLAG
        $data[4]  = 'Y';                            // MAILABLE_FLAG
        $data[5]  = 'N';                            // ARB_FLAG
        $data[6]  = 'N';                            // PREMIER_FLAG
        $data[7]  = null;                           // ASSOCIATION_ID
        $data[8]  = 19;                             // CO_NUM
        $data[9]  = null;                           // EXT_RECORD_ID
        $data[10] = $fullName;                      // FULL_NAME
        $data[11] = null;                           // PREFIX
        $data[12] = $user->getFirstName();          // FIRST NAME
        $data[13] = null;                           // MIDDLE_INITIAL
        $data[14] = $user->getLastName();           // LAST NAME
        $data[15] = null;                           // SUFFIX
        $data[16] = null;                           // COMPANY_NAME
        $data[17] = null;                           // ADDR_LINE_1
        $data[18] = null;                           // ADDR_LINE_2
        $data[19] = null;                           // ADDR_LINE_3
        $data[20] = null;                           // ADDR_LINE_4
        $data[21] = null;                           // ADDR_LINE_5
        $data[22] = null;                           // CITY
        $data[23] = null;                           // STATE_PROVINCE_CODE
        $data[24] = null;                           // POSTAL_CODE
        $data[25] = null;                           // ZIP
        $data[26] = null;                           // ZIP4
        $data[27] = $country;                       // COUNTRY_CODE
        $data[28] = null;                           // PHONE_AREA_CODE1
        $data[29] = null;                           // PHONE_NUM1
        $data[30] = null;                           // EXTENSION1
        $data[31] = null;                           // PHONE_AREA_CODE2
        $data[32] = null;                           // PHONE_NUM2
        $data[33] = null;                           // EXTENSION2
        $data[34] = null;                           // FAX_AREA_CODE
        $data[35] = null;                           // FAX_NUM
        $data[36] = 404005;                         // WEB_SOURCE_ID
        $data[37] = null;                           // BUYER_FLAG
        $data[38] = null;                           // LAST_PURCHASE_LOB
        $data[39] = null;                           // LAST_PURCHASE_DATE
        $data[40] = $created;                       // SOURCE_CREATE_DATE
        $data[41] = $created;                       // SOURCE_UPDATE_DATE
        $data[42] = "DHS_Gaming_IgamesAWARENA_eD";  // SOURCE_UPDATE_BY
        $data[43] = null;                           // FORMAT_PREFERENCE_CODE
        $data[44] = $language;                      // LANGUAGE_CODE
        $data[45] = "DHS";                          // SUBSCRIPTION_ID
        $data[46] = "GAMING";                       // VERSION_ID
        $data[47] = $created;                       // SUBSCRIPTION_FLAG_UPDATE_DATE
        $data[48] = "U";                            // GLOBAL_EMAILABLE_FLAG
        $data[49] = $created;                       // GLOBAL_EMAIL_FLAG_UPDATE_DATE
        $data[50] = $created;                       // SUBSCRIPTION_UPDATE_DATE
        $data[51] = 'Y';                            // EMAIL_DELIVERABLE_FLAG
        $data[52] = null;                           // PREF_GAME_GENRE
        $data[53] = $sysOwner;                      // PREF_AW_SYST_OWNER
        $data[54] = null;                           // PREF_DT_LT_BOTH
        $data[55] = null;                           // PREF_AW_SYSTEM
        $data[56] = $sysOwner;                      // PREF_MILITARY
        $data[57] = $user->getLatestNewsSource();   // PREF_SOURCE

        $dataString = implode('|~|', $data)."\r\n";

        return $dataString;
    }
}
