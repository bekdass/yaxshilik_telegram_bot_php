<?php

class Cities {
    
    // 1. Islom.uz ID raqamlari (PrayerService uchun)
    // Kichik shaharlarni eng yaqin katta shaharga bog'lab chiqdim, 
    // shunda user "Chust" desa ham, bot Namangan vaqtini bo'lsa ham olib beradi (bo'sh qaytmaydi).
    public static $islomUzIds = [
        // Toshkent va viloyat
        "toshkent"    => 27,
        "chirchiq"    => 27,
        "angren"      => 6,
        "olmaliq"     => 27, // Aslida Toshkentga yaqin
        "bekobod"     => 2,
        "yangiyol"    => 27,
        "parkent"     => 27,
        "gazalkent"   => 27,
        "boka"        => 27,
        "piskent"     => 27,

        // Andijon
        "andijon"     => 1,
        "xonobod"     => 20,
        "shahrixon"   => 22,
        "asaka"       => 3,
        "xojaobod"    => 1, // Markazga yaqin
        "marhamat"    => 1,
        "paytug"      => 1,
        "boston"      => 1,

        // Namangan
        "namangan"    => 15,
        "chust"       => 8,
        "chortoq"     => 15,
        "kosonsoy"    => 15,
        "uchqorgon"   => 15,
        "pop1"        => 15, // Pop
        "mingbuloq"   => 15,

        // Farg'ona
        "fargona"     => 37,
        "qoqon"       => 26,
        "margilon"    => 13,
        "quva"        => 12,
        "rishton"     => 17,
        "oltiariq"    => 19,
        "bogdod"      => 37,
        "beshariq"    => 37,

        // Sirdaryo
        "guliston"    => 5,
        "sirdaryo"    => 5,
        "shirin"      => 5,
        "yangiyer"    => 5,
        "boyovut"     => 5,
        "sardoba"     => 5,

        // Jizzax
        "jizzax"      => 9,
        "zomin"       => 50,
        "gallaorol"   => 9,
        "dostlik"     => 9,
        "forish"      => 9,

        // Samarqand
        "samarqand"   => 18,
        "kuttaqorgon" => 11, // Kattaqo'rg'on
        "kattaqorgon" => 11, 
        "urgut"       => 18,
        "bulungur"    => 18,
        "jomboy"      => 18,
        "ishtixon"    => 18,
        "mirbozor"    => 18,

        // Navoiy
        "navoiy"      => 14,
        "zarafshon"   => 24,
        "uchquduq"    => 23,
        "nurota"      => 14,
        "konimex"     => 14,

        // Buxoro
        "buxoro"      => 4,
        "gijduvon"    => 4,
        "qorakol"     => 4,
        "jondor"      => 4,
        "gazli"       => 4,

        // Qashqadaryo
        "qarshi"      => 25,
        "shahrisabz"  => 25,
        "guzor"       => 25,
        "muborak"     => 25,
        "dehqonobod"  => 25,

        // Surxondaryo
        "termiz"      => 74,
        "denov"       => 7,
        "sherobod"    => 74,
        "boysun"      => 74,
        "shorchi"     => 74,

        // Xorazm
        "urganch"     => 78,
        "xiva"        => 21,
        "shovot"      => 78,
        "xonqa"       => 78,
        "hazorasp"    => 78,
        "yangibozor"  => 78,

        // Qoraqalpog'iston
        "nukus"       => 16,
        "qongirot"    => 16,
        "moynoq"      => 16,
        "tortkol"     => 16,
        "taxtakopir"  => 16
    ];

    // 2. Namozvaqti.uz uchun sluglar
    public static $citysSlugs = [
        "toshkent" => "toshkent",
        "samarqand" => "samarqand",
        "buxoro" => "buxoro",
        "andijon" => "andijon",
        "namangan" => "namangan",
        "fargona" => "fargona",
        "navoiy" => "navoiy",
        "jizzax" => "jizzax",
        "termiz" => "termiz",
        "qarshi" => "qarshi",
        "urganch" => "urganch",
        "nukus" => "nukus",
        "guliston" => "guliston",
        "margilon" => "margilon",
        "qoqon" => "qoqon",
        "xiva" => "xiva",
    ];

    // 3. Chiroyli nomlar (Validatsiya va Bot javobi uchun - MUHIM)
    public static $citysNames = [
        "toshkent"    => "Toshkent",
        "chirchiq"    => "Chirchiq",
        "angren"      => "Angren",
        "olmaliq"     => "Olmaliq",
        "bekobod"     => "Bekobod",
        "yangiyol"    => "Yangiyo'l",
        "parkent"     => "Parkent",
        "gazalkent"   => "G'azalkent",
        "boka"        => "Bo'ka",
        "piskent"     => "Piskent",
        "andijon"     => "Andijon",
        "xonobod"     => "Xonobod",
        "shahrixon"   => "Shahrixon",
        "asaka"       => "Asaka",
        "xojaobod"    => "Xo'jaobod",
        "marhamat"    => "Marhamat",
        "paytug"      => "Paytug'",
        "boston"      => "Bo'ston",
        "namangan"    => "Namangan",
        "chust"       => "Chust",
        "chortoq"     => "Chortoq",
        "kosonsoy"    => "Kosonsoy",
        "uchqorgon"   => "Uchqo'rg'on",
        "pop1"        => "Pop",
        "mingbuloq"   => "Mingbuloq",
        "fargona"     => "Farg'ona",
        "qoqon"       => "Qo'qon",
        "margilon"    => "Marg'ilon",
        "quva"        => "Quva",
        "rishton"     => "Rishton",
        "oltiariq"    => "Oltiariq",
        "bogdod"      => "Bog'dod",
        "beshariq"    => "Beshariq",
        "guliston"    => "Guliston",
        "sirdaryo"    => "Sirdaryo",
        "shirin"      => "Shirin",
        "yangiyer"    => "Yangiyer",
        "boyovut"     => "Boyovut",
        "sardoba"     => "Sardoba",
        "jizzax"      => "Jizzax",
        "zomin"       => "Zomin",
        "gallaorol"   => "G'allaorol",
        "dostlik"     => "Do'stlik",
        "forish"      => "Forish",
        "samarqand"   => "Samarqand",
        "kattaqorgon" => "Kattaqo'rg'on",
        "kuttaqorgon" => "Kattaqo'rg'on",
        "urgut"       => "Urgut",
        "bulungur"    => "Bulung'ur",
        "jomboy"      => "Jomboy",
        "ishtixon"    => "Ishtixon",
        "mirbozor"    => "Mirbozor",
        "navoiy"      => "Navoiy",
        "zarafshon"   => "Zarafshon",
        "uchquduq"    => "Uchquduq",
        "nurota"      => "Nurota",
        "konimex"     => "Konimex",
        "buxoro"      => "Buxoro",
        "gijduvon"    => "G'ijduvon",
        "qorakol"     => "Qorako'l",
        "jondor"      => "Jondor",
        "gazli"       => "Gazli",
        "qarshi"      => "Qarshi",
        "shahrisabz"  => "Shahrisabz",
        "guzor"       => "G'uzor",
        "muborak"     => "Muborak",
        "dehqonobod"  => "Dehqonobod",
        "termiz"      => "Termiz",
        "denov"       => "Denov",
        "sherobod"    => "Sherobod",
        "boysun"      => "Boysun",
        "shorchi"     => "Sho'rchi",
        "urganch"     => "Urganch",
        "xiva"        => "Xiva",
        "shovot"      => "Shovot",
        "xonqa"       => "Xonqa",
        "hazorasp"    => "Hazorasp",
        "yangibozor"  => "Yangibozor",
        "nukus"       => "Nukus",
        "qongirot"    => "Qo'ng'irot",
        "moynoq"      => "Mo'ynoq",
        "tortkol"     => "To'rtko'l",
        "taxtakopir"  => "Taxtako'pir"
    ];

    // 4. Koordinatalar (LocationService ishlatadi)
    public static $cities = [
        // --- TOSHKENT SHAHRI VA VILOYATI ---
        "toshkent" => [41.2995, 69.2401],
        "chirchiq" => [41.4689, 69.5822],
        "angren" => [41.0100, 70.1400],
        "olmaliq" => [40.8500, 69.6000],
        "bekobod" => [40.2225, 69.2597],
        "yangiyol" => [41.1167, 69.0500],
        "parkent" => [41.2933, 69.6764],
        "gazalkent" => [41.5667, 69.7667],
        "boka" => [40.8111, 69.1978],
        "piskent" => [40.9300, 69.3500],

        // --- ANDIJON VILOYATI ---
        "andijon" => [40.7821, 72.3442],
        "xonobod" => [40.7956, 72.9847],
        "shahrixon" => [40.7111, 72.0528],
        "asaka" => [40.6333, 72.2333],
        "xojaobod" => [40.6667, 72.5667],
        "marhamat" => [40.5000, 72.3333],
        "paytug" => [40.9000, 72.2333],
        "boston" => [40.7333, 71.8667],

        // --- NAMANGAN VILOYATI ---
        "namangan" => [40.9983, 71.6726],
        "chust" => [41.0000, 71.2333],
        "chortoq" => [41.0667, 71.8167],
        "kosonsoy" => [41.2500, 71.5500],
        "uchqorgon" => [41.1167, 72.0833],
        "pop1" => [40.8736, 71.1089],
        "mingbuloq" => [40.8333, 71.3833],

        // --- FARG'ONA VILOYATI ---
        "fargona" => [40.3864, 71.7864],
        "qoqon" => [40.5286, 70.9426],
        "margilon" => [40.4833, 71.7167],
        "quva" => [40.5167, 72.0667],
        "rishton" => [40.3500, 71.2833],
        "oltiariq" => [40.3833, 71.4833],
        "bogdod" => [40.4500, 71.2000],
        "beshariq" => [40.4333, 70.6000],

        // --- SIRDARYO VILOYATI ---
        "guliston" => [40.4897, 68.7842],
        "sirdaryo" => [40.8500, 68.6667],
        "shirin" => [40.2333, 69.1167],
        "yangiyer" => [40.2667, 68.8167],
        "boyovut" => [40.4333, 68.9500],
        "sardoba" => [40.3333, 68.1500],

        // --- JIZZAX VILOYATI ---
        "jizzax" => [40.1204, 67.8283],
        "zomin" => [39.9606, 68.3958],
        "gallaorol" => [40.0167, 67.5833],
        "dostlik" => [40.5167, 68.0333],
        "forish" => [40.6167, 67.1833],

        // --- SAMARQAND VILOYATI ---
        "samarqand" => [39.6270, 66.9750],
        "kattaqorgon" => [39.8833, 66.2667],
        "urgut" => [39.4000, 67.2500],
        "bulungur" => [39.7667, 67.2667],
        "jomboy" => [39.6833, 67.0833],
        "ishtixon" => [39.9667, 66.4833],
        "mirbozor" => [39.9167, 65.9333],

        // --- NAVOIY VILOYATI ---
        "navoiy" => [40.0844, 65.3792],
        "zarafshon" => [41.5744, 64.2188],
        "uchquduq" => [42.1500, 63.5500],
        "nurota" => [40.5667, 65.6833],
        "konimex" => [40.2833, 65.1667],

        // --- BUXORO VILOYATI ---
        "buxoro" => [39.7681, 64.4556],
        "gijduvon" => [40.1000, 64.6667],
        "qorakol" => [39.5000, 63.8500],
        "jondor" => [39.7333, 64.1833],
        "gazli" => [40.1333, 63.4500],

        // --- QASHQADARYO VILOYATI ---
        "qarshi" => [38.8667, 65.8000],
        "shahrisabz" => [39.0500, 66.8333],
        "guzor" => [38.6167, 66.2500],
        "muborak" => [39.2667, 65.1500],
        "dehqonobod" => [38.3500, 66.5000],

        // --- SURXONDARYO VILOYATI ---
        "termiz" => [37.2242, 67.2783],
        "denov" => [38.2667, 67.9000],
        "sherobod" => [37.6667, 67.0000],
        "boysun" => [38.2000, 67.2000],
        "shorchi" => [38.0000, 67.7833],

        // --- XORAZM VILOYATI ---
        "urganch" => [41.5567, 60.6314],
        "xiva" => [41.3775, 60.3594],
        "shovot" => [41.6500, 60.3000],
        "xonqa" => [41.4833, 60.7667],
        "hazorasp" => [41.3167, 61.0833],
        "yangibozor" => [41.7333, 60.5667],

        // --- QORAQALPOG'ISTON RESPUBLIKASI ---
        "nukus" => [42.4619, 59.6166],
        "qongirot" => [43.0333, 58.8333],
        "moynoq" => [43.7667, 59.0333],
        "tortkol" => [41.5500, 61.0000],
        "taxtakopir" => [43.0167, 60.2833]
    ];
}