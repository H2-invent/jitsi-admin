<?php

namespace App\Tests\Util;

use App\Util\InputSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(InputSettings::class)]
class InputSettingsTest extends TestCase
{
    /**
     * @dataProvider provideConstantValues
     */
    public function testConstantHasExpectedValue(string $constantName, string $expectedValue): void
    {
        self::assertSame($expectedValue, constant(InputSettings::class . '::' . $constantName));
    }

    public static function provideConstantValues(): array
    {
        return [
            'PERSISTENT_ROOMS' => ['PERSISTENT_ROOMS', 'input_settings_persistant_rooms'],
            'PERSISTENT_ROOMS_DEFAULT' => ['PERSISTENT_ROOMS_DEFAULT', 'input_settings_persistant_rooms_default'],
            'ONLY_REGISTERED' => ['ONLY_REGISTERED', 'input_settings_only_registered'],
            'ONLY_REGISTERED_DEFAULT' => ['ONLY_REGISTERED_DEFAULT', 'input_settings_only_registered_default'],
            'SHARE_LINK' => ['SHARE_LINK', 'input_settings_share_link'],
            'SHARE_LINK_DEFAULT' => ['SHARE_LINK_DEFAULT', 'input_settings_share_link_default'],
            'MAX_PARTICIPANTS' => ['MAX_PARTICIPANTS', 'input_settings_max_participants'],
            'MAX_PARTICIPANTS_DEFAULT' => ['MAX_PARTICIPANTS_DEFAULT', 'input_settings_max_participants_default'],
            'WAITING_LIST' => ['WAITING_LIST', 'input_settings_waitinglist'],
            'WAITING_LIST_DEFAULT' => ['WAITING_LIST_DEFAULT', 'input_settings_waitinglist_default'],
            'CONFERENCE_JOIN_PAGE' => ['CONFERENCE_JOIN_PAGE', 'input_settings_conference_join_page'],
            'CONFERENCE_JOIN_PAGE_DEFAULT' => ['CONFERENCE_JOIN_PAGE_DEFAULT', 'input_settings_conference_join_page_default'],
            'DEACTIVATE_PARTICIPANTS_LIST' => ['DEACTIVATE_PARTICIPANTS_LIST', 'input_settings_deactivate_participantsList'],
            'DEACTIVATE_PARTICIPANTS_LIST_DEFAULT' => ['DEACTIVATE_PARTICIPANTS_LIST_DEFAULT', 'input_settings_deactivate_participantsList_default'],
            'DISALLOW_SCREENSHARE' => ['DISALLOW_SCREENSHARE', 'input_settings_dissallow_screenshare'],
            'DISALLOW_SCREENSHARE_DEFAULT' => ['DISALLOW_SCREENSHARE_DEFAULT', 'input_settings_dissallow_screenshare_default'],
            'ALLOW_SCHEDULING' => ['ALLOW_SCHEDULING', 'input_settings_allow_sheduling'],
            'ALLOW_ROOM_PLANNING' => ['ALLOW_ROOM_PLANNING', 'input_settings_allow_roomPlanning'],
            'ALLOW_ROOM_PLANNING_DEFAULT' => ['ALLOW_ROOM_PLANNING_DEFAULT', 'input_settings_allow_roomPlanning_default'],
            'ALLOW_TIMEZONE' => ['ALLOW_TIMEZONE', 'input_settings_allow_timezone'],
            'ALLOW_TIMEZONE_DEFAULT' => ['ALLOW_TIMEZONE_DEFAULT', 'input_settings_allow_timezone_default'],
            'ALLOW_LOBBY' => ['ALLOW_LOBBY', 'input_settings_allowLobby'],
            'ALLOW_LOBBY_DEFAULT' => ['ALLOW_LOBBY_DEFAULT', 'input_settings_allowLobby_default'],
            'ALLOW_TAG' => ['ALLOW_TAG', 'input_settings_allow_tag'],
            'ALLOW_EDIT_TAG' => ['ALLOW_EDIT_TAG', 'input_settings_allow_edit_tag'],
            'ALLOW_MAYBE_OPTION' => ['ALLOW_MAYBE_OPTION', 'INPUT_SETTINGS_ALLOW_MAYBE_OPTION'],
            'ALLOW_MAYBE_OPTION_DEFAULT' => ['ALLOW_MAYBE_OPTION_DEFAULT', 'INPUT_SETTINGS_ALLOW_MAYBE_OPTION_DEFAULT'],
            'ALLOW_SET_MAX_USERS' => ['ALLOW_SET_MAX_USERS', 'INPUT_ALLOW_SET_MAX_USERS'],
            'ALLOW_SET_MAX_USERS_DEFAULT' => ['ALLOW_SET_MAX_USERS_DEFAULT', 'INPUT_ALLOW_SET_MAX_USERS_DEFAULT'],
            'DISABLE_DOUBLE_OPT_IN' => ['DISABLE_DOUBLE_OPT_IN', 'INPUT_DISABLE_DOUBLE_OPT_IN'],
        ];
    }

    public function testAllConstantKeysAreUniqueNonEmptyStrings(): void
    {
        $constants = (new \ReflectionClass(InputSettings::class))->getConstants();

        self::assertCount(30, $constants);

        $values = [];
        foreach ($constants as $name => $value) {
            self::assertIsString($value, sprintf('Constant %s must be a string', $name));
            self::assertNotSame('', $value, sprintf('Constant %s must not be empty', $name));
            $values[] = $value;
        }

        self::assertSame([], array_diff_assoc($values, array_unique($values)), 'Configuration keys must not collide');
    }
}
