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

$url = "http://www.ireland.com/en-us/accommodation/all/1-";
$mech = WWW::Mechanize->new();
$mech->stack_depth(0);
$geo = WWW::Mechanize->new();
$coder = JSON::XS->new->ascii->pretty->allow_nonref;
$tree = HTML::Tree->new();
%data = ();
@headers = ("name", "email", "phone", "fax");


$workbook  = Excel::Writer::XLSX->new( 'ireland_info.xlsx' );
$worksheet = $workbook->add_worksheet();

#Column naming loop
for($h=0;$h<@headers;$h++)
{
    $worksheet->write(0, $h, $headers[$h]);
}


$row = 1; #Setting spreadsheet loop

#Webpage loop
for($i = 1; $i <= 99999; $i++)
{
    $page = sprintf("%05d", $i); #formating page number
    print $page."\n";
    
    eval
    {
        $response = $mech->get($url.$page);
    };
    
    #Bad page check
    if(!defined($response) || $response->code != 200)
    {
        print "Invalid page.\n\n";
    }
    
    #Good page check
    else
    {
        $html = $mech->content();
        $tree->parse($html);
        $data{'name'} = $tree->look_down( 'itemprop' , 'name' )->as_text;
        $data{'address'} = $tree->look_down( 'itemprop' , 'address' )->as_text;
        $data{'phone'} = $tree->look_down( 'itemprop' , 'telephone' )->as_text;
        $data{'fax'} = $tree->look_down( 'itemprop' , 'faxNumber' )->as_text;
        $data{'email'} = $tree->look_down( 'itemprop' , 'email' )->as_text;
        
        foreach $key (sort keys %data)
        {
            print $key." => ".$data{$key}."\n";
        }
        print "\n";
        
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
        %data = ();
        $row++;
    }
    
    sleep(5); #Sleep statement so not to hit the site too hard.
}#End of webpage loop

$workbook->close();