<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Governorate;
use Illuminate\Database\Seeder;

/**
 * Syria's 14 governorates with their cities and main towns (every district
 * centre plus the larger sub-district towns), Arabic + English names. For
 * Damascus — a single city — the main neighbourhoods are listed instead, so
 * a buyer can find ads near them.
 *
 * Safe to re-run on a live database: rows are matched on the Arabic name,
 * nothing is deleted or renamed, existing ids (used by ads and accounts) stay
 * the same; it only adds missing places and fills in blank English names.
 *
 *   php artisan db:seed --class=GeoSeeder --force
 *
 * Cities can also be switched off without deleting them (cities.is_active).
 */
class GeoSeeder extends Seeder
{
    public const DATA = [
        ['دمشق', 'Damascus', [
            ['دمشق', 'Damascus'], ['المزة', 'Mezzeh'], ['المالكي', 'Al-Malki'], ['أبو رمانة', 'Abu Rummaneh'],
            ['كفرسوسة', 'Kafr Sousa'], ['الميدان', 'Al-Midan'], ['باب توما', 'Bab Touma'], ['القصاع', 'Al-Qassaa'],
            ['باب شرقي', 'Bab Sharqi'], ['ركن الدين', 'Rukn al-Din'], ['المهاجرين', 'Al-Muhajirin'], ['الشعلان', 'Al-Shaalan'],
            ['الصالحية', 'Al-Salihiyah'], ['البرامكة', 'Al-Baramkeh'], ['برزة', 'Barzeh'], ['القابون', 'Al-Qaboun'],
            ['جوبر', 'Jobar'], ['العباسيين', 'Al-Abbasiyyin'], ['التجارة', 'Al-Tijara'], ['الشاغور', 'Al-Shaghour'],
            ['القدم', 'Al-Qadam'], ['دمر', 'Dummar'], ['مشروع دمر', 'Mashrou Dummar'], ['الزاهرة', 'Al-Zahira'],
            ['المجتهد', 'Al-Mujtahid'], ['الربوة', 'Al-Rabweh'], ['نهر عيشة', 'Nahr Aisha'], ['اليرموك', 'Yarmouk'],
        ]],
        ['ريف دمشق', 'Rif Dimashq', [
            ['دوما', 'Douma'], ['التل', 'Al-Tall'], ['الزبداني', 'Al-Zabadani'], ['جرمانا', 'Jaramana'],
            ['صحنايا', 'Sahnaya'], ['أشرفية صحنايا', 'Ashrafiyat Sahnaya'], ['جديدة عرطوز', 'Jdeidet Artouz'], ['قطنا', 'Qatana'],
            ['داريا', 'Darayya'], ['المعضمية', 'Al-Moadamiyah'], ['الكسوة', 'Al-Kiswah'], ['قدسيا', 'Qudsaya'],
            ['الهامة', 'Al-Hamah'], ['السيدة زينب', 'Sayyida Zaynab'], ['ببيلا', 'Babbila'], ['يلدا', 'Yalda'],
            ['حرستا', 'Harasta'], ['عربين', 'Arbin'], ['زملكا', 'Zamalka'], ['سقبا', 'Saqba'],
            ['كفر بطنا', 'Kafr Batna'], ['عدرا', 'Adra'], ['الضمير', 'Al-Dumayr'], ['النبك', 'Al-Nabk'],
            ['يبرود', 'Yabroud'], ['دير عطية', 'Deir Atiyah'], ['القطيفة', 'Al-Qutayfah'], ['معلولا', 'Maaloula'],
            ['صيدنايا', 'Saydnaya'], ['منين', 'Manin'], ['رنكوس', 'Rankous'], ['بلودان', 'Bloudan'],
            ['مضايا', 'Madaya'], ['سرغايا', 'Serghaya'], ['عين الفيجة', 'Ain al-Fijeh'],
            ['زاكية', 'Zakiyah'], ['كناكر', 'Kanaker'], ['قارة', 'Qara'], ['جيرود', 'Jayroud'],
        ]],
        ['حلب', 'Aleppo', [
            ['حلب', 'Aleppo'], ['اعزاز', 'Azaz'], ['منبج', 'Manbij'], ['الباب', 'Al-Bab'],
            ['عفرين', 'Afrin'], ['جرابلس', 'Jarablus'], ['عين العرب', 'Ain al-Arab (Kobani)'], ['السفيرة', 'Al-Safirah'],
            ['دير حافر', 'Deir Hafer'], ['مسكنة', 'Maskanah'], ['الأتارب', 'Al-Atarib'], ['دارة عزة', 'Daret Azza'],
            ['مارع', 'Marea'], ['تل رفعت', 'Tal Rifaat'], ['نبل', 'Nubl'], ['الزهراء', 'Al-Zahraa'],
            ['عندان', 'Anadan'], ['حريتان', 'Hreitan'], ['تادف', 'Tadef'], ['الراعي', 'Al-Rai'],
            ['أخترين', 'Akhtarin'], ['صوران', 'Souran'], ['جنديرس', 'Jindires'], ['راجو', 'Rajo'],
            ['الشيوخ', 'Al-Shuyukh'], ['خناصر', 'Khanasir'], ['الحاضر', 'Al-Hader'], ['الزربة', 'Al-Zirbah'],
        ]],
        ['حمص', 'Homs', [
            ['حمص', 'Homs'], ['تدمر', 'Palmyra'], ['الرستن', 'Al-Rastan'], ['تلبيسة', 'Talbiseh'],
            ['القصير', 'Al-Qusayr'], ['المخرم', 'Al-Mukharram'], ['تلكلخ', 'Tal Kalakh'], ['تلدو', 'Taldou'],
            ['كفرلاها', 'Kafr Laha'], ['القريتين', 'Al-Qaryatayn'], ['صدد', 'Sadad'], ['فيروزة', 'Fairouzeh'],
            ['زيدل', 'Zaidal'], ['الفرقلس', 'Al-Furqlus'], ['حسياء', 'Hisyah'], ['شين', 'Sheen'],
            ['الحواش', 'Al-Hawash'], ['مرمريتا', 'Marmarita'], ['الناصرة', 'Al-Nasirah'], ['قلعة الحصن', 'Qalaat al-Hosn'],
            ['السخنة', 'Al-Sukhnah'], ['الغنطو', 'Al-Ghantu'], ['عين النسر', 'Ain al-Nisr'], ['جوسية', 'Jusiyah'],
        ]],
        ['حماة', 'Hama', [
            ['حماة', 'Hama'], ['مصياف', 'Masyaf'], ['السلمية', 'Salamiyah'], ['محردة', 'Mhardeh'],
            ['السقيلبية', 'Al-Suqaylabiyah'], ['صوران', 'Souran'], ['طيبة الإمام', 'Tayyibat al-Imam'], ['حلفايا', 'Halfaya'],
            ['مورك', 'Morek'], ['كفرزيتا', 'Kafr Zita'], ['اللطامنة', 'Al-Lataminah'], ['كرناز', 'Karnaz'],
            ['قمحانة', 'Qamhana'], ['خطاب', 'Khattab'], ['قلعة المضيق', 'Qalaat al-Madiq'], ['سلحب', 'Salhab'],
            ['شطحة', 'Shathah'], ['جورين', 'Jourin'], ['كفرنبودة', 'Kafr Nabudah'], ['الحمراء', 'Al-Hamraa'],
            ['عقيربات', 'Uqayribat'], ['بري الشرقي', 'Barri al-Sharqi'], ['الصبورة', 'Al-Sabboura'], ['السعن', 'Al-Saan'],
            ['تلدرة', 'Tal Dara'], ['حربنفسه', 'Harbnafsah'], ['عوج', 'Ouj'], ['عين حلاقيم', 'Ain Halaqim'],
            ['وادي العيون', 'Wadi al-Uyun'], ['الربيعة', 'Al-Rabiah'], ['دير شميل', 'Deir Shamil'], ['جب رملة', 'Jubb Ramlah'],
            ['التمانعة', 'Al-Tamanaah'], ['الزيارة', 'Al-Ziyarah'],
        ]],
        ['اللاذقية', 'Latakia', [
            ['اللاذقية', 'Latakia'], ['جبلة', 'Jableh'], ['القرداحة', 'Qardaha'], ['الحفة', 'Al-Haffah'],
            ['كسب', 'Kessab'], ['صلنفة', 'Slinfah'], ['ربيعة', 'Rabia'], ['البهلولية', 'Al-Bahluliyah'],
            ['قسطل معاف', 'Qastal Maaf'], ['عين البيضا', 'Ain al-Bayda'], ['بيت ياشوط', 'Beit Yashout'], ['القطيلبية', 'Al-Qutaylibiyah'],
            ['عين شقاق', 'Ain Shiqaq'], ['حرف المسيترة', 'Harf al-Musaytirah'], ['كنسبا', 'Kinsabba'], ['المزيرعة', 'Al-Muzayraah'],
        ]],
        ['طرطوس', 'Tartus', [
            ['طرطوس', 'Tartus'], ['بانياس', 'Baniyas'], ['صافيتا', 'Safita'], ['الدريكيش', 'Al-Dreikish'],
            ['الشيخ بدر', 'Al-Shaykh Badr'], ['القدموس', 'Al-Qadmus'], ['مشتى الحلو', 'Mashta al-Helu'], ['الصفصافة', 'Al-Safsafah'],
            ['السودا', 'Al-Sawda'], ['الحميدية', 'Al-Hamidiyah'], ['حمين', 'Hamin'], ['كفرون', 'Kafroun'],
            ['يحمور', 'Yahmur'], ['العنازة', 'Al-Annazah'], ['بيت الشيخ يونس', 'Beit al-Shaykh Younes'], ['برمانة المشايخ', 'Barmanet al-Mashayekh'],
        ]],
        ['إدلب', 'Idlib', [
            ['إدلب', 'Idlib'], ['معرة النعمان', 'Maarat al-Numan'], ['أريحا', 'Ariha'], ['جسر الشغور', 'Jisr al-Shughur'],
            ['حارم', 'Harem'], ['سراقب', 'Saraqib'], ['سرمدا', 'Sarmada'], ['الدانا', 'Al-Dana'],
            ['معرة مصرين', 'Maarrat Misrin'], ['بنش', 'Binnish'], ['سلقين', 'Salqin'], ['كفرنبل', 'Kafr Nabl'],
            ['خان شيخون', 'Khan Shaykhun'], ['كفر تخاريم', 'Kafr Takharim'], ['أطمة', 'Atmeh'], ['دركوش', 'Darkush'],
            ['أرمناز', 'Armanaz'], ['تفتناز', 'Taftanaz'], ['سرمين', 'Sarmin'], ['قورقانيا', 'Qurqania'],
            ['كللي', 'Kili'], ['جرجناز', 'Jarjanaz'], ['حيش', 'Heish'], ['محمبل', 'Muhambal'],
            ['بداما', 'Bdama'], ['احسم', 'Ihsim'], ['التمانعة', 'Al-Tamanaah'], ['كفريا', 'Kafriya'],
        ]],
        ['الرقة', 'Raqqa', [
            ['الرقة', 'Raqqa'], ['الطبقة', 'Al-Tabqa'], ['تل أبيض', 'Tal Abyad'], ['سلوك', 'Suluk'],
            ['عين عيسى', 'Ain Issa'], ['المنصورة', 'Al-Mansurah'], ['معدان', 'Maadan'], ['الكرامة', 'Al-Karamah'],
            ['السبخة', 'Al-Sabkhah'], ['الجرنية', 'Al-Jurniyah'],
        ]],
        ['دير الزور', 'Deir ez-Zor', [
            ['دير الزور', 'Deir ez-Zor'], ['الميادين', 'Al-Mayadin'], ['البوكمال', 'Al-Bukamal'], ['هجين', 'Hajin'],
            ['الشحيل', 'Al-Shuhayl'], ['العشارة', 'Al-Asharah'], ['موحسن', 'Muhasan'], ['الصور', 'Al-Sour'],
            ['البصيرة', 'Al-Busayrah'], ['ذيبان', 'Dhiban'], ['الكسرة', 'Al-Kasrah'], ['التبني', 'Al-Tibni'],
            ['القورية', 'Al-Quriyah'], ['السوسة', 'Al-Susah'], ['الجلاء', 'Al-Jalaa'], ['صبيخان', 'Subaykhan'],
        ]],
        ['الحسكة', 'Al-Hasakah', [
            ['الحسكة', 'Al-Hasakah'], ['القامشلي', 'Qamishli'], ['رأس العين', 'Ras al-Ayn'], ['المالكية', 'Al-Malikiyah'],
            ['عامودا', 'Amuda'], ['الدرباسية', 'Al-Darbasiyah'], ['تل تمر', 'Tal Tamr'], ['الشدادي', 'Al-Shaddadi'],
            ['الهول', 'Al-Hawl'], ['القحطانية', 'Al-Qahtaniyah'], ['اليعربية', 'Al-Yaarubiyah'], ['الجوادية', 'Al-Jawadiyah'],
            ['معبدة', 'Maabadah'], ['مركدة', 'Markadah'], ['تل حميس', 'Tal Hamis'], ['عرب ويسو', 'Arab Wisu'],
        ]],
        ['درعا', 'Daraa', [
            ['درعا', 'Daraa'], ['إزرع', 'Izra'], ['الصنمين', 'Al-Sanamayn'], ['نوى', 'Nawa'],
            ['بصرى الشام', 'Bosra'], ['جاسم', 'Jasim'], ['طفس', 'Tafas'], ['الحراك', 'Al-Harak'],
            ['داعل', 'Dael'], ['الشيخ مسكين', 'Al-Shaykh Maskin'], ['إنخل', 'Inkhil'], ['المزيريب', 'Al-Muzayrib'],
            ['الجيزة', 'Al-Jizah'], ['خربة غزالة', 'Khirbet Ghazaleh'], ['اليادودة', 'Al-Yadudah'], ['الكرك الشرقي', 'Al-Karak al-Sharqi'],
            ['صيدا', 'Saida'], ['المسيفرة', 'Al-Musayfirah'], ['غباغب', 'Ghabaghib'], ['ابطع', 'Ibta'],
            ['تسيل', 'Tasil'], ['الشجرة', 'Al-Shajarah'], ['الحارة', 'Al-Harrah'], ['محجة', 'Mahajjah'],
            ['نصيب', 'Nasib'], ['بصر الحرير', 'Busr al-Harir'], ['تل شهاب', 'Tal Shihab'], ['عتمان', 'Atman'],
        ]],
        ['السويداء', 'As-Suwayda', [
            ['السويداء', 'As-Suwayda'], ['شهبا', 'Shahba'], ['صلخد', 'Salkhad'], ['القريا', 'Al-Qrayya'],
            ['عرمان', 'Orman'], ['المزرعة', 'Al-Mazraa'], ['شقا', 'Shaqqa'], ['ذيبين', 'Dhibeen'],
            ['عريقة', 'Ariqah'], ['المشنف', 'Al-Mushannaf'], ['قنوات', 'Qanawat'], ['عتيل', 'Atil'],
            ['ملح', 'Malah'], ['الصورة الكبيرة', 'Al-Soura al-Kabira'], ['الكفر', 'Al-Kafr'], ['ولغا', 'Walgha'],
        ]],
        ['القنيطرة', 'Quneitra', [
            ['القنيطرة', 'Quneitra'], ['خان أرنبة', 'Khan Arnabah'], ['مدينة البعث', 'Madinat al-Baath'], ['جباتا الخشب', 'Jubata al-Khashab'],
            ['حضر', 'Hadar'], ['الخشنية', 'Al-Khushniyah'], ['الرفيد', 'Al-Rafid'], ['بئر عجم', 'Bir Ajam'],
            ['مسحرة', 'Mashara'], ['جبا', 'Jaba'],
        ]],
    ];

    public function run(): void
    {
        foreach (self::DATA as [$governorateAr, $governorateEn, $cities]) {
            $governorate = Governorate::firstOrCreate(['name_ar' => $governorateAr], ['name_en' => $governorateEn]);
            if (blank($governorate->name_en)) {
                $governorate->update(['name_en' => $governorateEn]);
            }

            foreach ($cities as [$cityAr, $cityEn]) {
                $city = City::firstOrCreate(
                    ['governorate_id' => $governorate->id, 'name_ar' => $cityAr],
                    ['name_en' => $cityEn],
                );
                if (blank($city->name_en)) {
                    $city->update(['name_en' => $cityEn]);
                }
            }
        }
    }
}
