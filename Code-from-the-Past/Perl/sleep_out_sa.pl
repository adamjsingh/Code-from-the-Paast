#!/usr/bin/perl

use CGI;
use CGI::Session;
use WWW::Mechanize;
use HTML::TokeParser;
use HTML::TableExtract;
use HTML::Tree;
use HTML::Parser;
use File::Copy;
use HTML::Tree;
use Excel::Writer::XLSX;
use JSON::XS;

#Trim function that removes whitespace at the begining and end of a string
sub trim($)
{
    my $string = shift;
    $string =~ s/^\s+//;
    $string =~ s/\s+$//;
    $string =~ s/&nbsp;/ /g;
    return $string;
}

$url = "http://www.ghasa.co.za/";
$mech = WWW::Mechanize->new();
$mech->stack_depth(0);
$geo = WWW::Mechanize->new();
$coder = JSON::XS->new->ascii->pretty->allow_nonref;
$tree = HTML::Tree->new();
%data = ();
@headers = ("name", "email", "phone", "fax", "website", "manager");

@places = (
	'@_yamkela_guesthouse',
	"103_vaal_de_grace",
	"17_on_13th_guest_house",
	"17_ravensteyn_road",
	"18_on_kloof_bed_and_breakfast",
	"1881_shiraz_estate_guest_house",
	"21_kingfisher",
	"26_sunset_avenue_llandudno",
	"3_on_camps_bay",
	"3_palms_cape_kuilsriver",
	"3_palms_luxury_cottage_1",
	"3_palms_luxury_cottage",
	"3_westbury_house",
	"314_on_clark_guest_house",
	"38_edinburgh_court",
	"4_on_varneys",
	"40_winks",
	"5_camp_street_guesthouse_self_catering",
	"5_options_guest_house",
	"517_granger_granger_suites",
	"6_on_scott",
	"7_caribbean_sands",
	"7_uvongo_breeze",
	"7a_clifton_steps",
	"8_royal_palm_bnb",
	"aalwyns_guesthouse",
	"aan_dorpstraat_guest_house",
	"abalone_place",
	"abbey_guesthouse",
	"abloom_bush_lodge_and_spa_retreat",
	"acorn_house",
	"acorn_tree",
	"adato_guest_house",
	"adley_house",
	"african_dreams_bed_breakfast",
	"african_flair_country_lodge",
	"african_vineyard_guesthouse",
	"agulhas_ocean_art_house",
	"aj's_guesthouse",
	"akkerlaan_guesthouse",
	"algoa_guest_house",
	"alpha_du_cap_guest_house",
	"alpina_lodge",
	"alta_bay",
	"amakhosi_guesthouse",
	"amazulu_luxury_guesthouse",
	"ambleside_house",
	"an_african_villa",
	"ancient_emperor_guest_estate",
	"annes_place",
	"antoinettes",
	"antrim_villa",
	"applegarth_bb_and_selfcatering",
	"aquamarine_guest_house",
	"aristocats_guest_lodge",
	"arum_place_guesthouse",
	"arusha_lodge",
	"at_rest",
	"athenian_villa",
	"attache_guest_lodge",
	"avian_leisure",
	"aview",
	"avuxeni_lodge",
	"ayumu_guesthouse",
	"b_b_bloem",
	"b1001_ocean-view",
	"bach_suite_22",
	"bahari_beach_house",
	"baluleni_safari_lodge",
	"baluleni_safari_lodge_1",
	"bananien_lodge",
	"bankenveld_houst_bed_and_breakfast",
	"basse_provence",
	"baysands_lodge",
	"bayview_bed_breakfast",
	"baywind",
	"BeachB",
	"beach_music",
	"beachcomber_bay",
	"beaches_and_bays_guest_accommodation",
	"bed_breakfast_inn_between",
	"bekaru_lodge",
	"beluga_of_constantia",
	"berg_en_zee",
	"bergvallei_estates",
	"bergview",
	"berit_country_home_chapel",
	"beverley_country_cottages",
	"bichana_lodge_colleen_glen",
	"bickley_terraces_luxury_guesthouse",
	"birch_bush",
	"birdsong",
	"bishopsgrace",
	"blu_rose_cottage",
	"blue_mountain_guest_house",
	"blue_pebble",
	"blue_waters_beach_house",
	"bluegum_hill_guesthouse",
	"bougainvilla",
	"brackens_guest_house",
	"bradclin_beach_blouberg",
	"bradclin_house",
	"braeside_bed_breakfast",
	"buhle",
	"bushglam_luxury_holiday_home",
	"cactusberry_lodge",
	"caledon_country_house",
	"call_of_africa",
	"camelroc_guest_farm",
	"canle_guest_lodge",
	"capclassique",
	"cape_cottage",
	"cape_country_cottage",
	"cape_flame_guesthouse",
	"cape_st_francis_lifestyle_estate",
	"cape_town_sea_views_apartment",
	"cape_town_sea_views_villa",
	"casa_valencia",
	"casa_velha_guesthouse",
	"casa_vid_or",
	"casart_game_lodge",
	"casta_diva",
	"castle_in_clarens",
	"catohaven",
	"chalet_laurier",
	"chancellors_court",
	"charlitex_lodge",
	"chateau_de_carolle",
	"cherry_place_guest_house",
	"chislehurst_guest_house",
	"clarence_house",
	"clarens_manor",
	"claridge_house",
	"cloud_cottage",
	"cloud_house",
	"collin's_place",
	"constantia_guest_lodge",
	"continental_travel_inn",
	"cosy_cottage_bb",
	"cosy_coves",
	"cotswold_house",
	"country_lane_lodge",
	"courtyard_in_provence",
	"crooked_tree_cottage",
	"crystal_sands_guest_house",
	"danlee_overnight_accommodation",
	"darling_lodge_guest_house",
	"de_doornkraal_historic_country_house",
	"de_marchand_guesthouse",
	"de_oude_huize_yard",
	"de_pinna's_executive_guest_house",
	"de_waterkant_cottages",
	"destiny_lodge_cullinan",
	"dinkwe_guest_house",
	"dive_inn_pongola",
	"dreamers_guest_house",
	"dube_executive_suites",
	"dullstroom_country_cottages",
	"dunelm_gasteplaas",
	"duneside_guest_house",
	"ebony_and_ivory_guesthouse",
	"echo_point",
	"eden",
	"edward_charles_manor",
	"el_shaddai_accommodation",
	"elangeni_villa_garden_cottage",
	"elements",
	"emahlathini_guest_farm",
	"emerald_view",
	"entabeni_bb",
	"erlesmere_lodge",
	"evergreen_luxury_guest_suite",
	"fair_mountain_at_hout_bay",
	"fairview",
	"farquhar_lodge",
	"fatrez_guest_house_and_spa",
	"feathers_lodge",
	"felsensicht_holiday_home",
	"ferndale_lodge_bb",
	"fig_tree_bb",
	"firewings",
	"flame_lily_inn_guest_house",
	"flamingos_nest_guest_house",
	"four_flies_nature_farm",
	"four_rosmead",
	"fraai_uitzicht_1798",
	"fresay_executive_lodge",
	"fynbos_villa_guest_house",
	"gable_manor",
	"gariep_gardens",
	"gateway_apartments",
	"gateway_country_lodge",
	"gateway_guest_lodge",
	"gecko_house",
	"gem_bateleur_private_lodge",
	"ginnegaap_guest_house",
	"giovonna_bed_and_breakfast",
	"glenlin",
	"global_village_guest_house",
	"glory_hill_country_manor",
	"goedgedacht_farm",
	"golden_grove",
	"golfers_lodge",
	"goodeys_guesthouse",
	"goose_lodge",
	"gordons_beach_lodge",
	"grace_an_infanta_beach_house",
	"greatstays_guest_house",
	"greenlea_guest_house_conference_centre",
	"grendon_house",
	"gum_tree_manor",
	"halcyon_house",
	"harbour_view_lodge",
	"harfield_guest_villa",
	"harmonie_cottage",
	"harrison's_house",
	"hawkshead_lodge",
	"hawksmoor_house",
	"head_south_lodge",
	"heather_heights",
	"hedge_house",
	"henry_george_guesthouse",
	"heron_chase_guest_house",
	"hethersett",
	"hethersett_guest_house",
	"highlands_country_house",
	"hillcrest_manor",
	"hilltop_house",
	"holingsberg_chalet",
	"hoopoe_haven_guest_house",
	"hoopoe_house",
	"house_of_melville",
	"huijs_haerlem",
	"huntly_glen",
	"ikhaya_lodge",
	"inkosi_lodge",
	"invergara_lodge",
	"iqayiya_guest_house",
	"itaga_private_game_lodge",
	"itaga_private_game_lodgeb",
	"ithiliens_grace_guest_house",
	"jacaranda_house",
	"jambo_guest_house",
	"jardin_d_ebene_boutique_guesthouse",
	"jonkershuis_bb",
	"josco_smith_cottages",
	"karoo_view_cottages",
	"khaya_ndlovu_manor_house",
	"king_solomons_inn",
	"kingna_lodge",
	"kingslyn_boutique_guesthouse",
	"kites_view_bed_and_breakfast",
	"kla_g_sukkel",
	"klip_river_country_estate",
	"knysna_belle",
	"kolping_guest_house",
	"koubad_farm_lodge",
	"kruger_cottage",
	"kuilfontein_stable_cottages",
	"kwa_muzi_lodge",
	"la_campana_country_venue",
	"la_lechere_guest_house",
	"la_maison_de_promesse",
	"la_marija_guest_house",
	"la_pastorale",
	"la_petite_dauphine",
	"la_teranga_bed_and_breakfast",
	"la_villa_belle_ombre",
	"la_villa_vita",
	"lake_views",
	"lanseria_lodge",
	"lauberge_chanteclair",
	"lavender_cottage",
	"lazy_daze_bb_sc",
	"le_bay_guesthouse_and_self_catering",
	"le_chatelat",
	"le_cozmo_guest_house",
	"leafy_apartments",
	"leaves_luxury_lodge",
	"leisure_isle_lodge",
	"lentelus_gastehuis",
	"leolapa",
	"les_palmiers",
	"lezard_bleu",
	"life_on_3rd",
	"limestone_house",
	"linkside2",
	"lisa's_guesthouse",
	"little_eden_guest_lodge",
	"little_westerford_luxury_guest_house",
	"littlewood_cottage",
	"lodge_on_main_guest_house",
	"loloho_lodge",
	#"lost_trail_b_&_b",
	"lotus_blossom_accommodation",
	"lourens_river",
	"lourens_river_guesthouse",
	"maartens_guesthouse",
	"maison_sure_le_parc",
	"malherbe_guesthouse",
	"manor_on_the_bay_guesthouse",
	"marine_terrace",
	"matumi_golf_lodge",
	"maximillians",
	"melville_manor_guest_house",
	"mendelssohn_manor",
	"merwehuis_bed_and_breakfast",
	"middle_beach_house",
	"milton_lodge_self_catering",
	"mokoro_guest_house",
	"molenvliet_lodge",
	"mongoose_guesthouse",
	"montagu_vines_guest_house",
	"monte_vidéo_guest_house",
	"moody_river",
	"mooi_bly",
	"moolmanshof",
	"moonglow_guest_house",
	"mossel_bay_golf_lodge",
	"mount_everest_game_farm",
	"mountain_views_guest_house",
	"mtonjaneni_lodge",
	"mvurandona_private_game_lodge",
	"my_den_beachfront_bb_and_self_catering",
	"myrica",
	"nahoon_lodge",
	"naledzi_lodge",
	"newlands_house",
	"ninety_north_guesthouse",
	"nomndeni_de_la_changuion",
	"norscot_manor_guest_lodge",
	"number_6_b_n_b",
	"nupen_manor_bed_and_breakfast",
	"oakhampton",
	"oaklands_country_manor",
	"ocean_eleven",
	"Oceana_Palms",
	"olafs_guest_house",
	"old_joes_kaia",
	"olifantsrus_guest_house",
	"on_the_blue",
	"one_on_hely",
	"oppi_dam",
	"oppi-c_holiday_home",
	"orange_on_rose",
	"ou_kliphuis",
	"palamino_ridge",
	"palesa_guest_house",
	"palm_house",
	"panorama_guest_farm",
	"panorama_lodge",
	"paradiso_guest_house",
	"paternoster_dunes_guest_house",
	"pelenechi_manor",
	"penny_farthing_country_house",
	"pepper_cottages",
	"periwinkle_lodge",
	"plumbago_guest_house",
	"plumwood_inn",
	"pointb_guest_house",
	"pomegranate_b_n_b",
	"porcupine_pie_bouitique_lodge",
	"precept_ministries_guest_house",
	"president_lodge",
	"primavera_lodge",
	"prior_grange_guest_farm",
	"protea_ridge_guest_cottages",
	"queens_place",
	"rawsonville_house",
	"rayguts_guesthouse",
	"renaissance_guest_farm",
	"residence_klein_oliphants_hoek",
	"rickety_bridge_country_house",
	"rolbaken_guesthouse",
	"roodenburg_guest_house_1882",
	"rose_cottage",
	"rose_lodge",
	"rosedale_self_catering_guest_suite",
	"rus_n_bietjie_guesthouse",
	"safari_club_sa",
	"saffron_guest_house",
	"saffron_boutique_guesthouse",
	"sanambo_guesthouse",
	"sandstone_chameleon_guesthouse",
	"santa_lucia_guest_house",
	"scenery_guesthouse",
	"sea_cottages",
	"sea_shack",
	"sea_star_lodge",
	"seahorses_sea_view_apartment",
	"serengeti_self_catering_units",
	#"sharodin_bed_&amp;_breakfast",
	"sheilan_house",
	"shoreline_villa",
	"siesta_lodge_bb",
	"somerset_place",
	"southern_comfort_guest_lodge",
	"southern_cross",
	"southern_light_country_house",
	"spring_tide_inn",
	"st_croix_guest_cottages",
	"st_james_guest_house",
	"st_james_seaforth",
	"st_lucia_leopard_lodge",
	"st_lucia_wilds",
	#"s'tiba_guest_house",
	"stonecrop_guest_farm",
	"studio_28_clarens",
	"summerhill_guest_farm",
	"summerwood_guest_house",
	"sunbird_lodge",
	"sundays_river_mouth_guesthouse",
	"sunrise_vista_muizenberg",
	"sunset_rocks_accommodation",
	"tamboti_lodge_guest_house",
	"tanas_farm_house",
	"terra_casa",
	"the_3_chimneys_guest_house",
	"the_albatros_guest_house",
	"the_bay_atlantic_guest_house",
	"the_beach_house",
	"beach_house_hout_bay",
	"the_bushbaby_inn",
	"the_drey_lodge",
	"The_great_white_guesthouse",
	"the_green_tree_cottages",
	"the_hayloft",
	"the_hillside_house",
	"the_kimberley_club",
	"the_maple_village_guest_lodge_bb",
	"the_old_hatchery_guest_house",
	"the_old_trading_post",
	"the_one_8_hotel",
	"the_orion",
	"the_paddocks",
	"the_place",
	"the_plantation",
	"the_sandringham_bb",
	"the_tarragon",
	"the_terrace",
	"the_ultimate_guesthouse",
	"the_victorian_guest_house",
	"the_victorian_villa",
	"the_view",
	"the_villa",
	"the_villa_rosa",
	"the_wacky_bush_lodge",
	"thulani_lodge",
	"thunzi_bush_lodge",
	"tintagel_1",
	"tintagel_guest_house",
	"toms_guest_house",
	"topsy_turvy",
	"trade_winds",
	"trout_river_falls",
	"tudor_manor_guesthouse",
	"tulbagh_country_house",
	"twiga_lodge",
	"twin_oaks_guest_farm",
	"umhlanga_self_catering_guesthouse",
	"umlilo_lodge",
	"umzimvubu_retreat_guest_house",
	"underberg_guest_house",
	"upper_camps_bay",
	"vaal_streams",
	"verona_lodge",
	"viewpoint_villa",
	"view_t_full_lodge",
	"viking_sisters_guest_house",
	"villa_azure",
	"villa_beryl_guesthouse",
	"villa_garda_bb",
	"villa_hargreaves",
	"villa_honeywood_guest_house",
	"villa_l_apparita",
	"villa_la_palma",
	"villa_paradisa_guest_house",
	"villa_stella",
	"vip_cape_lodge",
	"volstruisvlei_guest_house",
	"wagtail_beach_house",
	"warthog_rest_private_lodge",
	"waterfall_guesthouse",
	"Waterford_guest_house",
	"watsonia_house",
	"way_up_cottage",
	"waybury_house",
	"wellwood_lodge",
	"weltevrede_gastehuis",
	"westwood_lodge",
	"white_aloe_guesthouse",
	"white_cottage",
	"white_house_lodge",
	"wild_olive_luxury_guest_house",
	"wildekrans_country_house",
	"wilderness_beach_lodge",
	"wildthingz_lodge",
	"willow_bb",
	"willow_rock",
	"wilton_manor",
	"wind_in_the_willows_manor_farm",
	"windermere_quinns_holiday_home",
	"winterton_country_lodge_rose_cottage",
	"withycombe_lodge",
	"zietsies",
);


$workbook  = Excel::Writer::XLSX->new( 'south_africa_info.xlsx' );
$worksheet = $workbook->add_worksheet();

for($h=0;$h<@headers;$h++)
{
    $worksheet->write(0, $h, $headers[$h]);
}

$url = "http://www.ghasa.co.za/";
$row = 1;

foreach $place (@places)
{
    %data = ();
    $mech->get($url.$place.".aspx");
    $html= $mech->content();
    
    $extract = HTML::TableExtract->new(attribs => {id => "ctl00_ctl04_tblMainDetail"});
    $extract->parse($html);
    
    @tables = $extract->tables();
    $table = $tables[0];
    
    @rows = $table->rows();
    
    if(defined($table))
    {
	$data{'name'} = trim($table->cell(0,1));
	$data{'name'} =~ s/([a-z])([A-Z])/$1 $2/;
    }
    
    if(@rows > 1)
    {
	$info = $table->cell(1,1);
	
	if($info =~ m/Tel:\s*(.*)\n/ig)
	{
	    $data{'phone'} = $1;
	}
	
	if($info =~ m/Fax:\s*(.*)\n/ig)
	{
	    $data{'fax'} = $1;
	}
	
	if($info =~ m/Email:\s*(.*)\n/ig)
	{
	    $data{'email'} = $1;
	}
	
	if($info =~ m/Website:\s*(.*)\n/ig)
	{
	    $data{'website'} = $1;
	}
	
	if($info =~ m/Manager:\s*(.*)\n/ig)
	{
	    $data{'manager'} = $1;
	}
	
	if($data{'phone'} =~ m/^Fax:/i)
	{
	    $data{'fax'} = $data{'phone'};
	    $data{'fax'} =~ s/Tel:\s*//i;
	    $data{'phone'} = "";
	}
	
	if($data{'fax'} =~ m/^Email:/i)
	{
	    $data{'email'} = $data{'fax'};
	    $data{'email'} =~ s/Email:\s*//i;
	    $data{'fax'} = "";
	}
    }
 
 
    print "-----------------------------------\n";
    foreach $key (sort keys %data)
    {
	print $key." => ".$data{$key}."\n";
    }
    print "-----------------------------------\n";
    
    for($h=0;$h<@headers;$h++)
    {
        $format = $workbook->add_format();
        $format->set_text_wrap();
        
        if($headers[$h] eq "website")
        {
            $worksheet->write_url($row, $h, $data{$headers[$h]}, $format) if(defined $data{$headers[$h]});
        }
        
        elsif( $headers[$h] eq "email")
        {
            $worksheet->write_url($row, $h, "mailto:".$data{$headers[$h]}, $format, $data{$headers[$h]}) if(defined $data{$headers[$h]});
        }
        
        else
        {
            $worksheet->write($row, $h, $data{$headers[$h]}, $format) if(defined $data{$headers[$h]});
        }
    }

    $row++;
    sleep(10);
}

$workbook->close();