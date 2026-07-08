<?php


namespace App;


use App\Entity\Rooms;
use App\Entity\User;

class UtilsHelper
{
    public static function slugify($urlString)
    {
        $slug = preg_replace("/[^a-zA-Z0-9 ]/", "", strtolower($urlString));
        $slug = preg_replace("/[ ]/", "_", $slug);
        return $slug;
    }

    public static function slugifywithDot($urlString)
    {
        $slug = preg_replace("/[^a-zA-Z0-9. ]/", "", strtolower($urlString));
        $slug = preg_replace("/[ ]/", "_", $slug);
        return $slug;
    }

    public static function readable_random_string($length = 6)
    {
        $nouns = [
            'cat', 'dog', 'robot', 'moon', 'tree', 'fish', 'flower', 'cloud', 'book', 'child',
            'star', 'mountain', 'river', 'stone', 'ocean', 'leaf', 'sun', 'window', 'dream', 'car',
            'ghost', 'house', 'hat', 'bottle', 'field', 'forest', 'shoe', 'mirror', 'sky', 'train',
            'music', 'door', 'chair', 'phone', 'apple', 'banana', 'monster', 'lamp', 'table', 'dragon',
            'storm', 'road', 'tiger', 'pencil', 'beach', 'pirate', 'camera', 'castle', 'guitar', 'keyboard',
            'socks', 'tunnel', 'planet', 'rabbit', 'wizard', 'balloon', 'shadow', 'message', 'engine', 'helicopter',
            'plane', 'ball', 'basket', 'clock', 'toaster', 'engineer', 'rocket', 'helmet', 'card', 'potion',
            'map', 'snowflake', 'arrow', 'cup', 'flame', 'ladder', 'cookie', 'cave', 'fire', 'whale',
            'feather', 'bubble', 'coin', 'circle', 'paint', 'sign', 'rope', 'suitcase', 'ship', 'lantern',
            'flag', 'wall', 'gem', 'quill', 'net', 'bridge', 'crown', 'bookcase', 'bell', 'breeze',
            'sandwich', 'telescope', 'owl', 'raven', 'duck', 'flock', 'nest', 'branch', 'seed', 'root',
            'note', 'flute', 'violin', 'portal', 'gate', 'key', 'lock', 'nail', 'glove', 'hammer',
            'brush', 'shell', 'chessboard', 'candle', 'box', 'tower', 'island', 'wave', 'glow', 'bug',
            'circuit', 'screen', 'wheel', 'gear', 'code', 'laser', 'crystal', 'sock', 'wire', 'spring',
            'nut', 'bolt', 'fountain', 'hose', 'dust', 'ink', 'sponge', 'torch', 'antenna', 'radar',
            'fog', 'glitch', 'pixel', 'echo', 'sound', 'trail', 'mist', 'beam', 'flare', 'spark',
            'ring', 'chain', 'vine', 'iceberg', 'tent', 'ballpit', 'mushroom', 'hammock', 'beetle', 'pillow'
        ];

        $adjectives = [
            'blue', 'green', 'quiet', 'strange', 'tiny', 'lazy', 'brave', 'happy', 'fuzzy', 'bright',
            'shiny', 'cold', 'hot', 'warm', 'gentle', 'wild', 'soft', 'hard', 'silent', 'loud',
            'fast', 'slow', 'ancient', 'modern', 'funny', 'sad', 'angry', 'weird', 'friendly', 'fierce',
            'bold', 'timid', 'rich', 'poor', 'young', 'old', 'massive', 'small', 'giant', 'sharp',
            'dull', 'golden', 'silver', 'broken', 'new', 'used', 'rusty', 'clean', 'dirty', 'sparkling',
            'dark', 'light', 'purple', 'yellow', 'orange', 'pink', 'red', 'black', 'white', 'gray',
            'striped', 'spotted', 'fluffy', 'hairy', 'bald', 'spiky', 'sneaky', 'clumsy', 'sleepy', 'alert',
            'magical', 'mystic', 'robotic', 'natural', 'round', 'square', 'triangular', 'flat', 'hollow', 'solid',
            'creaky', 'noisy', 'icy', 'burning', 'wet', 'dry', 'sticky', 'smooth', 'rough', 'bumpy',
            'twisted', 'melting', 'frozen', 'alive', 'dead', 'electric', 'solar', 'mechanical', 'digital', 'analog',
            'playful', 'serious', 'curious', 'shy', 'wise', 'clever', 'lucky', 'unlucky', 'calm', 'stormy',
            'hectic', 'peaceful', 'fearless', 'colorful', 'cloudy', 'sunny', 'windy', 'dusty', 'muddy', 'greasy',
            'fragrant', 'smelly', 'tasty', 'bitter', 'sweet', 'sour', 'salty', 'savory', 'juicy', 'dry',
            'crunchy', 'chewy', 'bland', 'spicy', 'burnt', 'raw', 'cooked', 'boiled', 'fried', 'grilled',
            'steamed', 'baked', 'roasted', 'frozen', 'chilled', 'warm', 'cozy', 'drafty', 'humid', 'icy',
            'stormy', 'snowy', 'rainy', 'foggy', 'misty', 'clear', 'blazing', 'glowing', 'dim', 'twinkling'
        ];

        $verbs = [
            'jumps', 'runs', 'flies', 'sleeps', 'dances', 'floats', 'hides', 'sings', 'crawls', 'spins',
            'rolls', 'slides', 'dives', 'climbs', 'skips', 'wanders', 'travels', 'glows', 'explodes', 'screams',
            'whispers', 'laughs', 'cries', 'smiles', 'shakes', 'builds', 'destroys', 'paints', 'writes', 'draws',
            'blinks', 'waves', 'drives', 'bakes', 'burns', 'eats', 'drinks', 'sits', 'stands', 'waits',
            'listens', 'hears', 'sees', 'touches', 'grabs', 'throws', 'catches', 'pushes', 'pulls', 'breaks',
            'fixes', 'turns', 'opens', 'closes', 'locks', 'unlocks', 'connects', 'disconnects', 'charges', 'zaps',
            'programs', 'downloads', 'uploads', 'types', 'codes', 'transforms', 'melts', 'freezes', 'evaporates', 'grows',
            'shrinks', 'multiplies', 'divides', 'fades', 'flashes', 'vibrates', 'glitches', 'buzzes', 'rings', 'echoes',
            'flickers', 'crashes', 'spreads', 'collapses', 'emerges', 'vanishes', 'appears', 'hovers', 'drifts', 'storms',
            'rains', 'snows', 'thunders', 'booms', 'bounces', 'twists', 'shines', 'spits', 'howls', 'claps',
            'cheers', 'groans', 'moans', 'rattles', 'snaps', 'squeaks', 'snores', 'yawns', 'gulps', 'growls',
            'chirps', 'croaks', 'bleats', 'roars', 'meows', 'barks', 'quacks', 'neighs', 'oinks', 'buzzes',
            'clicks', 'ticks', 'beeps', 'whistles', 'chants', 'murmurs', 'hums', 'clunks', 'zips', 'plops'
        ];

        $prepositions = [
            'under', 'over', 'behind', 'beside', 'on', 'near', 'above', 'below', 'between', 'within',
            'around', 'across', 'against', 'through', 'into', 'out of', 'next to', 'in front of', 'beneath', 'at',
            'by', 'with', 'without', 'before', 'after', 'along', 'towards', 'onto', 'off', 'beyond',
            'amid', 'among', 'inside', 'outside', 'around', 'upon', 'down', 'up', 'past', 'alongside',
            'opposite', 'nearby', 'beneath', 'underneath', 'in', 'on top of', 'about', 'concerning', 'despite', 'during',
            'except', 'like', 'unlike', 'via', 'per', 'amidst', 'amongst', 'notwithstanding', 'since', 'till',
            'throughout', 'to', 'toward', 'underneath', 'versus', 'via', 'within', 'without', 'as', 'barring',
            'counting', 'following', 'excluding', 'including', 'notwithstanding', 'regarding', 'respecting', 'save', 'than', 'until',
            'versus', 'upon', 'aboard', 'across from', 'ahead of', 'apart from', 'as far as', 'as of', 'as well as', 'aside from',
            'atop', 'because of', 'close to', 'due to', 'far from', 'inside of', 'near to', 'next to', 'on behalf of', 'on top of',
            'out from', 'outside of', 'prior to', 'pursuant to', 'rather than', 'subsequent to', 'such as', 'thanks to', 'together with', 'up to'
        ];

        // Pick random words
        $adj1 = $adjectives[array_rand($adjectives)];
        $noun1 = $nouns[array_rand($nouns)];
        $verb = $verbs[array_rand($verbs)];
        $prep = $prepositions[array_rand($prepositions)];
        $adj2 = $adjectives[array_rand($adjectives)];
        $noun2 = $nouns[array_rand($nouns)];

        // Build sentence
        $sentence = "The $adj1 $noun1 $verb $prep the $adj2 $noun2";

        return ucfirst($sentence);
    }

    public static function isAllowedToOrganizeRoom(?User $user, ?Rooms $room): bool
    {
        if (!$user) {
            return false;
        }
        if (!$room){
            return  false;
        }
        if (
            ($user === $room->getCreator() && $room->getModerator() && in_array($user, $room->getModerator()->getDeputy()->toArray())) ||
            $user === $room->getModerator() ||
            UtilsHelper::roomGeneratedByOtherDeputy($room, $user)
        ) {
            return true;
        }
        return false;
    }

    public static function isRoomReadOnly(Rooms $rooms, User $user): bool
    {
        if (
            $user === $rooms->getModerator() ||
            $rooms->getUser()->contains($user) ||
            UtilsHelper::roomGeneratedByOtherDeputy($rooms, $user)
        ) {
            return false;
        }
        return true;

    }

    public static function roomGeneratedByOtherDeputy(Rooms $rooms, User $user): bool
    {
        if (
            $rooms->getCreator() !== $rooms->getModerator()
            && $rooms->getModerator()
            && in_array($user, $rooms->getModerator()->getDeputy()->toArray())
        ) {
            return true;
        } else {
            return false;
        }
    }

    public static function isAllowedToOrganizeLobby(?User $user, Rooms $room): bool
    {
        if (!$user) {
            return false;
        }
        if ($user === $room->getModerator() || $user->getPermissionForRoom($room)->getLobbyModerator()) {
            return true;
        }
        return false;
    }

    public static function hasModeratorRights(?User $user, Rooms $room): bool
    {
        if (!$user) {
            return false;
        }
        if ($user === $room->getModerator() || $user->getPermissionForRoom($room)->getLobbyModerator()) {
            return true;
        }
        return false;
    }


}