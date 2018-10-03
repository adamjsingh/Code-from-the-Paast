#!/usr/bin/perl

# Modules
require 'admin.pl';
use lib qw( /home/ashish/perl );
use DBI;
use CGI;
use CGI::Session;
use File::Copy;
use Date::Parse;
use LWP::Simple;
use DateTime;
use Spreadsheet::ParseExcel;
use Spreadsheet::XLSX;
use Time::Local;
use Text::CSV_XS;
use Text::Iconv;
use WWW::Mechanize;
use HTML::TokeParser;
use HTML::TableExtract;
use HTML::Tree;
use Clone qw(clone);
use JSON::XS;

#Trim function that removes whitespace at the begining and end of a string
sub trim($)
{
    my $string = shift;
    $string =~ s/^\s+//;
    $string =~ s/\s+$//;
    return $string;
}

#Initializing Tree
$mech = WWW::Mechanize->new();
$tree = HTML::TreeBuilder->new();
$mech->get("http://patdrilling.com/rigs");
$html = $mech->content();

$json = JSON::XS->new->ascii->pretty->allow_nonref;

#print $html."\n";
$tree->parse_file($html);
@list_items = $tree->look_down("tag", "li");

#$html =~ m/var rig_list = (.*?)\s*<\/script>/;
$html =~ m/var rig_list = (.*)\n(.*)/;
$file_txt = $1.$2;
#print "779\n" if($file_txt =~ m/"id":"779"/);
$file_txt =~ s/<\/script>//;
$file_txt =~ s/;//;
$file_txt = trim($file_txt);
#print $file_txt."\n";
$json_txt = $json->decode($file_txt);
#$file_txt = "{\"rigs\": ".$file_txt."}";
#while($file_txt =~ m/;/g)
#{
#    print "Found semicolon\n";
#}


foreach $rig (@{$json_txt})
{
    #print $1."\n";
    $values{'rigid'} = $rig->{"id"}."\n";
    $values{'Y_COORD'} = $rig->{"lat"}."\n";
    $values{'X_COORD'} = $rig->{"lng"}."\n";
}