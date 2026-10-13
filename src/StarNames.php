<?php

declare(strict_types=1);

namespace BirthStars;

/** Turns catalog designations ("Alp", 33, "Psc") into names a person can read. */
final class StarNames
{
    /** abbreviation => [nominative, genitive, kid-friendly meaning] */
    public const CONSTELLATIONS = [
        'And' => ['Andromeda', 'Andromedae', 'the Chained Princess'],
        'Ant' => ['Antlia', 'Antliae', 'the Air Pump'],
        'Aps' => ['Apus', 'Apodis', 'the Bird of Paradise'],
        'Aqr' => ['Aquarius', 'Aquarii', 'the Water Bearer'],
        'Aql' => ['Aquila', 'Aquilae', 'the Eagle'],
        'Ara' => ['Ara', 'Arae', 'the Altar'],
        'Ari' => ['Aries', 'Arietis', 'the Ram'],
        'Aur' => ['Auriga', 'Aurigae', 'the Charioteer'],
        'Boo' => ['Boötes', 'Boötis', 'the Herdsman'],
        'Cae' => ['Caelum', 'Caeli', 'the Chisel'],
        'Cam' => ['Camelopardalis', 'Camelopardalis', 'the Giraffe'],
        'Cnc' => ['Cancer', 'Cancri', 'the Crab'],
        'CVn' => ['Canes Venatici', 'Canum Venaticorum', 'the Hunting Dogs'],
        'CMa' => ['Canis Major', 'Canis Majoris', 'the Great Dog'],
        'CMi' => ['Canis Minor', 'Canis Minoris', 'the Little Dog'],
        'Cap' => ['Capricornus', 'Capricorni', 'the Sea Goat'],
        'Car' => ['Carina', 'Carinae', 'the Keel of a ship'],
        'Cas' => ['Cassiopeia', 'Cassiopeiae', 'the Queen'],
        'Cen' => ['Centaurus', 'Centauri', 'the Centaur'],
        'Cep' => ['Cepheus', 'Cephei', 'the King'],
        'Cet' => ['Cetus', 'Ceti', 'the Sea Monster'],
        'Cha' => ['Chamaeleon', 'Chamaeleontis', 'the Chameleon'],
        'Cir' => ['Circinus', 'Circini', 'the Compass'],
        'Col' => ['Columba', 'Columbae', 'the Dove'],
        'Com' => ['Coma Berenices', 'Comae Berenices', "Berenice's Hair"],
        'CrA' => ['Corona Australis', 'Coronae Australis', 'the Southern Crown'],
        'CrB' => ['Corona Borealis', 'Coronae Borealis', 'the Northern Crown'],
        'Crv' => ['Corvus', 'Corvi', 'the Crow'],
        'Crt' => ['Crater', 'Crateris', 'the Cup'],
        'Cru' => ['Crux', 'Crucis', 'the Southern Cross'],
        'Cyg' => ['Cygnus', 'Cygni', 'the Swan'],
        'Del' => ['Delphinus', 'Delphini', 'the Dolphin'],
        'Dor' => ['Dorado', 'Doradus', 'the Swordfish'],
        'Dra' => ['Draco', 'Draconis', 'the Dragon'],
        'Equ' => ['Equuleus', 'Equulei', 'the Little Horse'],
        'Eri' => ['Eridanus', 'Eridani', 'the River'],
        'For' => ['Fornax', 'Fornacis', 'the Furnace'],
        'Gem' => ['Gemini', 'Geminorum', 'the Twins'],
        'Gru' => ['Grus', 'Gruis', 'the Crane'],
        'Her' => ['Hercules', 'Herculis', 'the Hero'],
        'Hor' => ['Horologium', 'Horologii', 'the Clock'],
        'Hya' => ['Hydra', 'Hydrae', 'the Water Snake'],
        'Hyi' => ['Hydrus', 'Hydri', 'the Little Water Snake'],
        'Ind' => ['Indus', 'Indi', 'the Indian'],
        'Lac' => ['Lacerta', 'Lacertae', 'the Lizard'],
        'Leo' => ['Leo', 'Leonis', 'the Lion'],
        'LMi' => ['Leo Minor', 'Leonis Minoris', 'the Little Lion'],
        'Lep' => ['Lepus', 'Leporis', 'the Hare'],
        'Lib' => ['Libra', 'Librae', 'the Scales'],
        'Lup' => ['Lupus', 'Lupi', 'the Wolf'],
        'Lyn' => ['Lynx', 'Lyncis', 'the Lynx'],
        'Lyr' => ['Lyra', 'Lyrae', 'the Harp'],
        'Men' => ['Mensa', 'Mensae', 'the Table Mountain'],
        'Mic' => ['Microscopium', 'Microscopii', 'the Microscope'],
        'Mon' => ['Monoceros', 'Monocerotis', 'the Unicorn'],
        'Mus' => ['Musca', 'Muscae', 'the Fly'],
        'Nor' => ['Norma', 'Normae', 'the Carpenter\'s Square'],
        'Oct' => ['Octans', 'Octantis', 'the Octant'],
        'Oph' => ['Ophiuchus', 'Ophiuchi', 'the Serpent Bearer'],
        'Ori' => ['Orion', 'Orionis', 'the Hunter'],
        'Pav' => ['Pavo', 'Pavonis', 'the Peacock'],
        'Peg' => ['Pegasus', 'Pegasi', 'the Winged Horse'],
        'Per' => ['Perseus', 'Persei', 'the Hero Perseus'],
        'Phe' => ['Phoenix', 'Phoenicis', 'the Phoenix'],
        'Pic' => ['Pictor', 'Pictoris', 'the Painter\'s Easel'],
        'Psc' => ['Pisces', 'Piscium', 'the Fishes'],
        'PsA' => ['Piscis Austrinus', 'Piscis Austrini', 'the Southern Fish'],
        'Pup' => ['Puppis', 'Puppis', 'the Stern of a ship'],
        'Pyx' => ['Pyxis', 'Pyxidis', 'the Compass'],
        'Ret' => ['Reticulum', 'Reticuli', 'the Net'],
        'Sge' => ['Sagitta', 'Sagittae', 'the Arrow'],
        'Sgr' => ['Sagittarius', 'Sagittarii', 'the Archer'],
        'Sco' => ['Scorpius', 'Scorpii', 'the Scorpion'],
        'Scl' => ['Sculptor', 'Sculptoris', 'the Sculptor'],
        'Sct' => ['Scutum', 'Scuti', 'the Shield'],
        'Ser' => ['Serpens', 'Serpentis', 'the Serpent'],
        'Sex' => ['Sextans', 'Sextantis', 'the Sextant'],
        'Tau' => ['Taurus', 'Tauri', 'the Bull'],
        'Tel' => ['Telescopium', 'Telescopii', 'the Telescope'],
        'Tri' => ['Triangulum', 'Trianguli', 'the Triangle'],
        'TrA' => ['Triangulum Australe', 'Trianguli Australis', 'the Southern Triangle'],
        'Tuc' => ['Tucana', 'Tucanae', 'the Toucan'],
        'UMa' => ['Ursa Major', 'Ursae Majoris', 'the Great Bear'],
        'UMi' => ['Ursa Minor', 'Ursae Minoris', 'the Little Bear'],
        'Vel' => ['Vela', 'Velorum', 'the Sails of a ship'],
        'Vir' => ['Virgo', 'Virginis', 'the Maiden'],
        'Vol' => ['Volans', 'Volantis', 'the Flying Fish'],
        'Vul' => ['Vulpecula', 'Vulpeculae', 'the Little Fox'],
    ];

    public const GREEK = [
        'Alp' => ['Alpha', 'α'], 'Bet' => ['Beta', 'β'], 'Gam' => ['Gamma', 'γ'], 'Del' => ['Delta', 'δ'],
        'Eps' => ['Epsilon', 'ε'], 'Zet' => ['Zeta', 'ζ'], 'Eta' => ['Eta', 'η'], 'The' => ['Theta', 'θ'],
        'Iot' => ['Iota', 'ι'], 'Kap' => ['Kappa', 'κ'], 'Lam' => ['Lambda', 'λ'], 'Mu' => ['Mu', 'μ'],
        'Nu' => ['Nu', 'ν'], 'Xi' => ['Xi', 'ξ'], 'Omi' => ['Omicron', 'ο'], 'Pi' => ['Pi', 'π'],
        'Rho' => ['Rho', 'ρ'], 'Sig' => ['Sigma', 'σ'], 'Tau' => ['Tau', 'τ'], 'Ups' => ['Upsilon', 'υ'],
        'Phi' => ['Phi', 'φ'], 'Chi' => ['Chi', 'χ'], 'Psi' => ['Psi', 'ψ'], 'Ome' => ['Omega', 'ω'],
    ];

    /** "Alp-1" -> "Alpha¹" style Greek name, or null. */
    public static function greek(?string $bayer): ?string
    {
        if ($bayer === null || $bayer === '') {
            return null;
        }
        if (!preg_match('/^([A-Za-z]+)(?:-?(\d))?$/', $bayer, $m)) {
            return null;
        }
        $key = ucfirst(strtolower($m[1]));
        if (!isset(self::GREEK[$key])) {
            return null;
        }
        $sup = ['1' => '¹', '2' => '²', '3' => '³', '4' => '⁴', '5' => '⁵', '6' => '⁶', '7' => '⁷', '8' => '⁸', '9' => '⁹'];

        return self::GREEK[$key][0] . (isset($m[2]) ? $sup[$m[2]] : '');
    }

    public static function constellation(?string $abbr): ?string
    {
        return $abbr !== null ? (self::CONSTELLATIONS[$abbr][0] ?? null) : null;
    }

    public static function constellationMeaning(?string $abbr): ?string
    {
        return $abbr !== null ? (self::CONSTELLATIONS[$abbr][2] ?? null) : null;
    }

    /** Scientific designation, e.g. "Alpha Centauri", "61 Cygni", "HIP 12345". */
    public static function designation(Star $s): string
    {
        $gen = $s->constellation !== null ? (self::CONSTELLATIONS[$s->constellation][1] ?? $s->constellation) : null;
        $greek = self::greek($s->bayer);
        if ($greek !== null && $gen !== null) {
            return "$greek $gen";
        }
        if ($s->flamsteed !== null && $gen !== null) {
            return "{$s->flamsteed} $gen";
        }
        if ($s->hr !== null) {
            return "HR {$s->hr}";
        }
        if ($s->hip !== null) {
            return "HIP {$s->hip}";
        }
        if ($s->gliese !== null) {
            return $s->gliese;
        }

        return "HYG {$s->hygId}";
    }

    /** The name to show in big letters: proper name if it has one, else the designation. */
    public static function displayName(Star $s): string
    {
        return $s->proper ?? self::designation($s);
    }
}
