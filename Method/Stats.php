<?php
namespace GDO\DogSlapwarz\Method;

use GDO\Core\GDO_ArgError;
use GDO\Core\GDT;
use GDO\Core\GDT_String;
use GDO\Dog\DOG_Command;
use GDO\Dog\DOG_Message;
use GDO\Dog\DOG_User;
use GDO\Dog\GDT_DogUser;
use GDO\DogSlapwarz\DOG_SlapHistory;
use GDO\DogSlapwarz\Module_DogSlapwarz;

final class Stats extends DOG_Command
{

    public function getCLITrigger(): string
    {
        return 'slapstats';
    }

    public function gdoParameters(): array
    {
        return [
            GDT_DogUser::make('user')->notNull()->thyself(),
        ];
    }

    /**
     * @throws GDO_ArgError
     */
    public function getUser(): DOG_User
    {
        return $this->gdoParameterValue('user');
    }

    public function dogExecute(DOG_Message $message, DOG_User $dogUser): GDT
    {
        $mod = Module_DogSlapwarz::instance();

        $user = $dogUser->getGDOUser();

        $data = DOG_SlapHistory::table()->select('SUM(slap_damage), COUNT(*)')->where("slap_user={$user->getID()}")->exec()->fetchRow();
        list($dmgDealt, $numSlaps) = $data;

        $data = DOG_SlapHistory::table()->select('SUM(slap_damage), COUNT(*)')->where("slap_target={$user->getID()}")->exec()->fetchRow();
        list($dmgTaken, $numSlapped) = $data;

        $method = Slap::make();
        $remainscore = $method->getConfigValueRoom($message->room, 'remainslap_score');
        $numRemains = $mod->userSettingValue($user, 'slapwarz_remainslaps');

        $total = $dmgDealt - $dmgTaken - ($numRemains * $remainscore);

        $total2 = $mod->userSettingValue($user, 'slapwarz_score');

        # %s has slapped other people %s times (%s points) and got slapped %s times (%s points). %s remainslaps. This sums up to %s points.
        return GDT_String::make()->var(t('msg_dog_slap_stats', [
            $user->renderUserName(),
            $numSlaps,
            $dmgDealt,
            $numSlapped,
            -$dmgTaken,
            $numRemains,
            -$remainscore,
            $total,
            $total2,
        ]));
    }

}
