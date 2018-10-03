#!/usr/bin/perl

require 'admin.pl';
use DBI;
use CGI;
use CGI::Session;
use WWW::Mechanize;
use HTML::TokeParser;
use HTML::TableExtract qw(tree);
use HTML::Parser;
use File::Copy;
use Date::Parse;
use LWP::Simple;
use HTML::TreeBuilder;

#Trim function that removes whitespace at the begining and end of a string
sub trim($)
{
    my $string = shift;
    $string =~ s/^\s+//;
    $string =~ s/\s+$//;
    return $string;
}

#Function to adjust time
sub change_time($)
{
    $date = shift;
    #Date matches format conditionals
    if($date =~ m/(\d+)\/(\d+)\/(\d+)\s+(\d+):(\d+)\s*(\w+)/i)
    {
       $month = $1;
       $day = $2;
       $year = $3;
       $hour = $4;
       $minute = $5;
       $half = uc($6);
       
       #Setting the hour correctly for a 24 hour clock
       $hour += 12 if($half eq "PM");
       return $year."-".sprintf("%02d", $month)."-".sprintf("%02d", $day)." ".sprintf("%02d", $hour).":".sprintf("%02d", $minute);
    }

    return "0000-00-00 00:00";

}#End of change time


@urls = ("http://infopost.bwpmlp.com/Notice/NoticeListPage.aspx?NoticeCategoryCode=C&tspid=", "http://infopost.bwpmlp.com/Notice/NoticeListPage.aspx?NoticeCategoryCode=N&tspid=",
         "http://infopost.bwpmlp.com/Frameset.aspx?url=%2FNotice%2FNoticeListPage.aspx%3FNoticeCategoryCode%3DP&tspid=");

@levels = ("Critical", "Non-Critical", "Planned Service Outages");

#Initializing company
$company = $dbh->quote("3");

#Levels for loop
for($i=0; $i<3; $i++)
{
    #initialize vars
    $mech = WWW::Mechanize->new();
    $extract = HTML::TableExtract->new(attrib => {id => "dgMatrix"});
    $level = $dbh->quote($levels[$i]);
    $notice_id = '';
    $notice_type = '';
    $post_time = '';
    $notice_eff = '';
    $notice_end = '';
    $status = '';
    $prior_notice_id = '';
    $response_req = 'No';
    $response_req_date = '';
    $critical_ind = '';
    $subject = '';
    $body  = '';
    $info = '' ;  #initialize it
    
    #tspid for loop
    for $tspid (1 .. 4)
    {
        #Extract the correct table from the webpage
        $mech->get($urls[$i].$tspid);
        $html = $mech->content();
        $extract->parse($html);
        @tables = $extract->tables();
        $flag = 0;
        $flag = 1 if(@tables != 0);
        #print "Number of tables found: ".@tables."\n";
        
        #Data found loop
        while($flag == 1)
        {
            $table = $tables[-2];
            
            #Collecting rows
            @rows = $table->rows();
            
            $r = 0; #Row marker
            
            #Rows for loop
            foreach $row (@rows)
            {
                #Header conditional
                if($r != 0 && ($r < @rows-1))
                {
                    $notice_type = $dbh->quote(trim($row->[0]->as_text));
                    $post_time = $dbh->quote(change_time(trim($row->[1]->as_text)));
                    $notice_eff = $dbh->quote(change_time(trim($row->[2]->as_text)));
                    $notice_end = $dbh->quote(change_time(trim($row->[3]->as_text)));
                    $notice_id = $dbh->quote(trim($row->[4]->as_text));
                    $status = $dbh->quote(trim($row->[5]->as_text));
                    $subject = $dbh->quote(trim($row->[6]->as_text));
                    $response_req_date = $dbh->quote(change_time(trim($row->[7]->as_text)));
                    
                    if($response_req_date ne "0000-00-00 00:00")
                    {
                        $response_req = $dbh->quote("Yes");
                    }
                    
                    else
                    {
                        $response_req = $dbh->quote("No");
                    }
                    
                    print "Level => ".$level."\n";
                    print "Notice Type => ".$notice_type."\n";
                    print "Post Time => ".$post_time."\n";
                    print "Notice Effective => ".$notice_eff."\n";
                    print "Notice End => ".$notice_end."\n";
                    print "Notice ID => ".$notice_id."\n";
                    print "Status => ".$status."\n";
                    print "Subject => ".$subject."\n";
                    print "Response Date => ".$response_req_date."\n";
                    print "Response Required => ".$response_req."\n";
                    
                    #Strings used to query the data base for checks, updates and inserts for data wells injection.
                    $update = "data_notice WHERE company = ".$company." AND notice_id = ".$notice_id;
                    
                    $condition = "data_notice WHERE company = ".$company." AND notice_id = ".$notice_id." AND level = ".$level." AND ";
                    $condition .= "notice_type = ".$notice_type." AND post_time = ".$post_time." AND notice_eff = ".$notice_eff." AND ";
                    $condition .= "status = ".$status." AND notice_end = ".$notice_end." AND subject = ".$subject." AND ";
                    $condition .= "response_req_date = ".$response_req_date." AND response_req = ".$response_req;
                    
                    $query = "data_notice SET company = ".$company.", notice_id = ".$notice_id.", level = ".$level.", ";
                    $query .= "notice_type = ".$notice_type.", post_time = ".$post_time.", notice_eff = ".$notice_eff.", ";
                    $query .= "status = ".$status.", notice_end = ".$notice_end.", subject = ".$subject.", ";
                    $query .= "response_req_date = ".$response_req_date.", response_req = ".$response_req;
                    
                    #Check to see if complete records exists.
                    $sql = "SELECT * FROM ".$condition;
                    #print $sql."\n";
                    #sleep(7);
                    $sth = $dbh->prepare($sql);
                    $sth->execute();
                    $well_entry = $sth->fetchrow_hashref;
                    
                    if(defined($well_entry)) #If complete record found.
                    {
                        print $notice_id." already exists.\n\n";
                    }
                    
                    else #Complete record not found.
                    {
                        #Check to see if the key values exists.
                        $sql = "SELECT * FROM ".$update;
                        #print $sql."\n";
                        #sleep(7);
                        $sth = $dbh->prepare($sql);
                        $sth->execute();
                        $well_entry = $sth->fetchrow_hashref;
                        
                        #If keys found, update.
                        if(defined($well_entry))
                        {
                            $update =~ s/data_notice//;
                            $sql = "UPDATE ".$query.$update;
                            print $notice_id." is being updated.\n\n";
                        }
                        
                        #Otherwise, insert new.
                        else
                        {
                            $sql = "INSERT INTO ".$query;
                            print $notice_id." is being inserted.\n\n";
                        }
                        
                        #print $sql."\n";
                        #sleep(7);
                        #Execute update or insert.
                        $sth = $dbh->prepare($sql);
                        $sth->execute();
                    }#End of complete record not found else
                }#End of header conditional
                $r++; #Increment row marker
            }#End of rows for loop
            
            @tables = ();
            $flag = 0;
            $flag = 1 if($html =~ m/<a href="javascript:__doPostBack\('dgMatrix\$_ctl4\$_ctl1',''\)">Next/);
            $mech->form_name("webForm1");
            $mech->field("__EVENTTARGET", "dgMatrix\$_ctl1\$_ctl1");
            $mech->field("__EVENTARGUMENT", "");
            $mech->submit_form();
            
            $html = $mech->content();
            $extract->parse($html);
            @tables = $extract->tables();
        }#End of data found loop
    }#End of tspid for loop
}#End of levels for loop