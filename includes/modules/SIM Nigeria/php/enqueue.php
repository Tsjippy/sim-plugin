<?php
namespace SIM\SIMNIGERIA;
use SIM;

$NigeriaStates	= [
    'Abia' => [
        'lat'=>'5.532003041',
        'lon'=>'7.486002487'
    ],
    'Abuja'=>[
        'lat'=>'9.083333333',
        'lon'=>'7.533333'
    ],
    'Adamawa'=>[
        'lat'=>'10.2703408',
        'lon'=>'13.2700321'
    ],
    'Akwa Ibom'=>[
        'lat'=>'5.007996056',
        'lon'=>'7.849998524'
    ],
    'Anambra'=>[
        'lat'=>'6.210433572',
        'lon'=>'7.06999711'
    ],
    'Bauchi'=>[
        'lat'=>'11.68040977',
        'lon'=>'10.190013'
    ],
    'Benue'=>[
        'lat'=>'7.190399596',
        'lon'=>'8.129984089'
    ],
    'Borno'=>[
        'lat'=>'10.62042279',
        'lon'=>'12.18999467'
    ],
    'Cross_River'=>[
        'lat'=>'4.960406513',
        'lon'=>'8.330023558'
    ],
    'Delta'=>[
        'lat'=>'5.890427265',
        'lon'=>'5.680004434'
    ],
    'Edo'=>[
        'lat'=>'6.340477314',
        'lon'=>'5.620008096'
    ],
    'Ekiti'=>[
        'lat'=>'7.630372741',
        'lon'=>'5.219980834'
    ],
    'Enugu'=>[
        'lat'=>'6.867034321',
        'lon'=>'7.383362995'
    ],
    'Gombe'=>[
        'lat'=>'10.29044293',
        'lon'=>'11.16995357'
    ],
    'Imo'=>[
        'lat'=>'5.492997053',
        'lon'=>'7.026003588'
    ],
    'Jigawa'=>[
        'lat'=>'11.7991891',
        'lon'=>'9.350334607'
    ],
    'Kaduna'=>[
        'lat'=>'11.0799813',
        'lon'=>'7.710009724'
    ],
    'Kano'=>[
        'lat'=>'11.99997683',
        'lon'=>'8.5200378'
    ],
    'Katsina'=>[
        'lat'=>'11.5203937',
        'lon'=>'7.320007689'
    ],
    'Kebbi'=>[
        'lat'=>'12.45041445',
        'lon'=>'4.199939737'
    ],
    'Kogi'=>[
        'lat'=>'7.800388203',
        'lon'=>'6.739939737'
    ],
    'Kwara'=>[
        'lat'=>'8.490010192',
        'lon'=>'4.549995889'
    ],
    'Lagos'=>[
        'lat'=>'6.443261653',
        'lon'=>'3.391531071'
    ],
    'Nassarawa'=>[
        'lat'=>'8.490423603',
        'lon'=>'8.5200378'
    ],
    'Niger'=>[
        'lat'=>'10.4003587',
        'lon'=>'5.469939737'
    ],
    'Ogun'=>[
        'lat'=>'7.160427265',
        'lon'=>'3.350017455'
    ],
    'Ondo'=>[
        'lat'=>'7.250395934',
        'lon'=>'5.199982054'
    ],
    'Osun'=>[
        'lat'=>'7.629959329',
        'lon'=>'4.179992634'
    ],
    'Oyo'=>[
        'lat'=>'7.970016092',
        'lon'=>'3.590002806'
    ],
    'Plateau'=>[
        'lat'=>'9.929973978',
        'lon'=>'8.890041055'
    ],
    'Rivers'=>[
        'lat'=>'4.810002257',
        'lon'=>'7.010000772'
    ],
    'Sokoto'=>[
        'lat'=>'13.06001548',
        'lon'=>'5.240031289'
    ],
    'Taraba'=>[
        'lat'=>'7.870409769',
        'lon'=>'9.780012572'
    ],
    'Yobe'=>[
        'lat'=>'11.74899608',
        'lon'=>'11.96600457'
    ],
    'Zamfara'=>[
        'lat'=>'12.1704057',
        'lon'=>'6.659996296'
    ],
];

$QuotaNames				= [
    'Administrative Workers',
    'Allied Health Professionals',
    'Allied Health Workers',
    'Community Workers',
    'Dental Personnel',
    'Dental Surgein',
    'Evangelical Personnel',
    'Evangelism Consultants',
    'Family Practice',
    'Internal Medicine',
    'Medical Consultants',
    'Missionaries',
    'Missionaries Managers',
    'Missionary Administrative Workers',
    'Missionary Support Services',
    'Nurses Anaestherists',
    'Nurses Educators',
    'Palliative Care Specialist',
    'Rural Development Workers',
    'Special Teachers',
    'Technical Personnel',
    'Teachers',
    'Theological Educators',
    'Theological Teachers',
    'Theological Translator'
];

add_action( 'wp_enqueue_scripts', function(){
    wp_enqueue_script( 'sim_quotajs', plugins_url('js/quota.js', __DIR__), array('sim_other_script'), ModuleVersion,true);
});