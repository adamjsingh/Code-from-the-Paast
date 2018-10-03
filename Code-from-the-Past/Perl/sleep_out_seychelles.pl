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

$url = "http://www.seychelles.travel/en/products/accommodation.php?aid=";
$mech = WWW::Mechanize->new();
$mech->stack_depth(0);
$geo = WWW::Mechanize->new();
$coder = JSON::XS->new->ascii->pretty->allow_nonref;
$tree = HTML::Tree->new();
%data = ();
@headers = ("name", "email", "island", "type", "setting", "address", "gps", "telephone", "fax", "website", "description");


$workbook  = Excel::Writer::XLSX->new( 'seychelles_info.xlsx' );
$worksheet = $workbook->add_worksheet();

for($h=0;$h<@headers;$h++)
{
    $worksheet->write(0, $h, $headers[$h]);
}


$row = 1;
for($i = 279; $i < 701; $i++)
{
    $mech->get($url.$i);
    $html = $mech->content();
    $tree->parse($html);
    #print "Number Tables: ".@tables."\n";
    #print "i is ".$i."\n";
    
    $html =~ m/<title>(.*?)<\/title>/;
    $data{'name'} = $1;
    $data{'name'} =~ s/Accommodation -//;
    $data{'name'} =~ s/&#8217;/'/g;
    $data{'name'} =~ s/&#233;/é/g;
    $data{'name'} =~ s/&#244;/ô/g;
    $data{'name'} =~ s/&#180;/´/g;
    $data{'name'} = trim($data{'name'});
    
    $html =~ m/<a href="mailto:(.*?)"/;
    $data{'email'} = $1;
    $data{'email'} = trim($data{'email'});
    
    if($data{'name'} ne '' && $data{'email'} ne '')
    {
        $extract = HTML::TableExtract->new(attribs => { class => "btxt" });
        $extract->parse($html);
        @tables = $extract->tables;
        $table = $tables[0];
        
        $data{'island'} = $table->cell(0, 0);
        $data{'island'} =~ s/Island\s*://i;
        $data{'island'} = trim($data{'island'});
        
        $data{'type'} = $table->cell(1, 0);
        $data{'type'} =~ s/Type.*?of.*?Hotel.*?:\W*//i;
        $data{'type'} = trim($data{'type'});
        
        $data{'setting'} = $table->cell(2, 0);
        $data{'setting'} =~ s/Setting\s*://i;
        $data{'setting'} = trim($data{'setting'});
        
        $table = $tables[1];
        @rows = $table->rows();
        
        foreach $row (@rows)
        {
            if($row->[0] =~ m/Address/i)
            {
                $data{'address'} = trim($row->[1]);
            }
            
            elsif($row->[0] =~ m/Tel/i)
            {
                $data{'telephone'} = trim($row->[1]);
            }
            
            elsif($row->[0] =~ m/Fax/i)
            {
                $data{'fax'} = trim($row->[1]);
            }
            
            elsif($row->[0] =~ m/Website/i)
            {
                $data{'website'} = trim($row->[1]);
            }
        }
        
        $html =~ m/<span class="btxt">(.*?)<\/span>/;
        $data{'description'} = $1;
        
        $gAddress = $data{'address'};
        $gAddress =~ s/\s+/%20/g;
        $gurl = "http://maps.googleapis.com/maps/api/geocode/json?address=%22".$gAddress."%22&sensor=true";
        $geo->get($gurl);
        $json = $geo->content();
        $returned = $coder->decode($json);
        
        if($returned->{"status"} eq "OK")
        {
            $data{'gps'} = trim($returned->{"results"}->[0]->{"geometry"}->{"location"}->{"lat"}).", ".trim($returned->{"results"}->[0]->{"geometry"}->{"location"}->{"lng"});
        }
        
        else
        {
            $data{'gps'} = "Not Found.";
        }
        
        print"--------------------------\n";
        
        foreach $key (sort keys %data)
        {
            print $key." : ".$data{$key}."\n";
        }
        print"--------------------------\n\n";
        
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
        sleep(10);
        %data = ();
        $row++;
    }
}

$workbook->close();