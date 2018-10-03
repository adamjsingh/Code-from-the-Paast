#!/usr/bin/perl

# Modules
require ('admin.pl');
use lib qw( /home/ashish/perl );
use DBI;
use CGI;
use CGI::Session;
use File::Copy;
use Date::Parse;
use LWP::Simple;
use DateTime;
use Time::Local;
use Spreadsheet::XLSX;

#Trim function that removes whitespace at the begining and end of a string
sub trim($)
{
    my $string = shift;
    $string =~ s/^\s+//;
    $string =~ s/\s+$//;
    return $string;
}

#Converts integer into a date.  The integer is the number of days past
#December 31st 1899.  If the int is 1 the date is January 1st 1900.
#This only works for dates after December 31st 1899.
#The date is formatted yyyy-mm-dd to match that of mySQL.
sub int2date($)
{
    #Hash of days in each month. Feb. will be added later, based on year.
    my %months =(
        '01' => '31',
        '03' => '31',
        '04' => '30',
        '05' => '31',
        '06' => '30',
        '07' => '31',
        '08' => '31',
        '09' => '30',
        '10' => '31',
        '11' => '30',
        '12' => '31',
    );
    my $total_days = int(shift); #Type cast as int to remove decimal used for hours, minutes and seconds.
    return "0000-00-00" if($total_days <= 0);
    $total_days = int($total_days);
    my $years = int($total_days/365); #Finding the number of years in the days.
    #Find the number of days remaining in the current year, accounting for leap years.
    my $remaining_days = (int($total_days)%365)-(int($years)/4);
    $remaining_days = int($remaining_days);
    
    #Go back a year conditional
    if($remaining_days <= 0)
    {
        if(int($years%4) == 0) #If the year is a leap year, add 366 days
        {
            $remaining_days += 366;
        }
        
        else #Otherwise, add 365
        {
            $remaining_days += 365;
        }
        $years--;
    }
    
    if(int($years%4) == 0) #If the year is a leap year, Feb. has 29 days.
    {
        $months{'02'} = 29;
    }
    
    else #Otherwise, Feb. has 28 days.
    {
        $months{'02'} = 28;
    }
    
    #For Loop to find the month based on the days remaining in the year.
    #Keys must be sorted for accuracy.
    foreach $key (sort keys %months)
    {
        #If there are more days remaing than there are in the current month,
        #subtract the month from the days and go on to the next month.
        if($remaining_days > $months{$key})
        {
           $remaining_days -= int($months{$key}); 
        }
        
        #Otherwise, add 1900 to the year and return the date.
        else
        {
            $years += 1900;
            return int($years)."-".$key."-".sprintf("%02d", int($remaining_days));
        }
    }
    
    #Returns a date of zeros if there is an error.
    return "0000-00-00";
}#End of int2date

#Creating parser and parsing file
print "Parsing file: ".$ARGV[0]."\n";
$parser = Spreadsheet::XLSX->new($ARGV[0]);
die "File does not exist.\n" if(!defined($parser));
$worksheet = $parser->worksheet(ARGV[0]);
die "Invalid File.\n" if(!defined($worksheet));

#Setting up bounds of the spreadsheet.
$row_min = $worksheet->{MinRow};
$row_max = $worksheet->{MaxRow};
$col_min = $worksheet->{MinCol};
$col_max = $worksheet->{MaxCol};

#Rows for loop
for $row ($row_min .. $row_max)
{
    #Header condiional
    if($row == $row_min)
    {
        #Collumns for loop
        for $col ($col_min .. $col_max)
        {
            $temp = $worksheet->get_cell($row, $col)->unformatted();
            $temp = lc($temp);
            $temp =~ s/ /_/g;
            
            if($temp =~ m/friday_date/i)
            {
                $types{'whenx'} = $col;
            }
            
            elsif($temp =~ m/operator/i)
            {
                $types{'company'} = $col;
            }
            
            elsif($temp =~ m/proposed_depth/i)
            {
                $types{'td'} = $col;
            }
            
            else
            {
                $types{$temp} = $col;
            }
        }#End of collumns for loop
        
        #Debuging print loop
        #foreach $key (sort keys %types)
        #{
        #    print $key." => ".$types{$key}."\n";
        #}#End of debuging print loop
        #print "\n";
        #sleep(10);
    }#End of header conditional
    
    #Non-Header conditional
    else
    {
        #Setting all of the values
        $values{'whenx'} = "0000-00-00";
        $values{'whenx'} = int2date(trim($worksheet->get_cell($row, $types{'whenx'})->unformatted())) if(defined($worksheet->get_cell($row, $types{'whenx'})) && trim($worksheet->get_cell($row, $types{'whenx'})->unformatted()) ne "");
        
        $values{'spud_date'} = "0000-00-00";
        $values{'spud_date'} = int2date(trim($worksheet->get_cell($row, $types{'spud_date'})->unformatted())) if(defined($worksheet->get_cell($row, $types{'spud_date'})) && trim($worksheet->get_cell($row, $types{'spud_date'})->unformatted()) ne "");
        
        $values{'state'} = "";
        $values{'state'} = trim($worksheet->get_cell($row, $types{'state'})->unformatted()) if(defined($worksheet->get_cell($row, $types{'state'})) && trim($worksheet->get_cell($row, $types{'state'})->unformatted()) ne "");
        
        $values{'county'} = "";
        $values{'county'} = trim($worksheet->get_cell($row, $types{'county'})->unformatted()) if(defined($worksheet->get_cell($row, $types{'county'})) && trim($worksheet->get_cell($row, $types{'county'})->unformatted()) ne "");
        
        $values{'country'} = "";
        $values{'country'} = trim($worksheet->get_cell($row, $types{'country'})->unformatted()) if(defined($worksheet->get_cell($row, $types{'country'})) && trim($worksheet->get_cell($row, $types{'country'})->unformatted()) ne "");
        
        $values{'company'} = "";
        $values{'company'} = trim($worksheet->get_cell($row, $types{'company'})->unformatted()) if(defined($worksheet->get_cell($row, $types{'company'})) && trim($worksheet->get_cell($row, $types{'company'})->unformatted()) ne "");
        
        $values{'parent_company'} = "";
        $values{'parent_company'} = trim($worksheet->get_cell($row, $types{'parent_company'})->unformatted()) if(defined($worksheet->get_cell($row, $types{'parent_company'})) && trim($worksheet->get_cell($row, $types{'parent_company'})->unformatted()) ne "");
        
        $values{'contractor'} = "";
        $values{'contractor'} = trim($worksheet->get_cell($row, $types{'contractor'})->unformatted()) if(defined($worksheet->get_cell($row, $types{'contractor'})) && trim($worksheet->get_cell($row, $types{'contractor'})->unformatted()) ne "");
        
        $values{'rigid'} = "";
        $values{'rigid'} = trim($worksheet->get_cell($row, $types{'rigid'})->unformatted()) if(defined($worksheet->get_cell($row, $types{'rigid'})) && trim($worksheet->get_cell($row, $types{'rigid'})->unformatted()) ne "");
        
        $values{'rig_type'} = "";
        $values{'rig_type'} = trim($worksheet->get_cell($row, $types{'rig_type'})->unformatted()) if(defined($worksheet->get_cell($row, $types{'rig_type'})) && trim($worksheet->get_cell($row, $types{'rig_type'})->unformatted()) ne "");
        
        $values{'well_type'} = "";
        $values{'well_type'} = trim($worksheet->get_cell($row, $types{'well_type'})->unformatted()) if(defined($worksheet->get_cell($row, $types{'well_type'})) && trim($worksheet->get_cell($row, $types{'well_type'})->unformatted()) ne "");
        
        $values{'well_target'} = "";
        $values{'well_target'} = trim($worksheet->get_cell($row, $types{'well_target'})->unformatted()) if(defined($worksheet->get_cell($row, $types{'well_target'})) && trim($worksheet->get_cell($row, $types{'well_target'})->unformatted()) ne "");           
        
        $values{'horizontal'} = "";
        $values{'horizontal'} = trim($worksheet->get_cell($row, $types{'horizontal'})->unformatted()) if(defined($worksheet->get_cell($row, $types{'horizontal'})) && trim($worksheet->get_cell($row, $types{'horizontal'})->unformatted()) ne "");
        
        $values{'well_status'} = "";
        $values{'well_status'} = trim($worksheet->get_cell($row, $types{'well_status'})->unformatted()) if(defined($worksheet->get_cell($row, $types{'well_status'})) && trim($worksheet->get_cell($row, $types{'well_status'})->unformatted()) ne "");
        
        $values{'area_name'} = "";
        $values{'area_name'} = trim($worksheet->get_cell($row, $types{'area_name'})->unformatted()) if(defined($worksheet->get_cell($row, $types{'area_name'})) && trim($worksheet->get_cell($row, $types{'area_name'})->unformatted()) ne "");
        
        $values{'area_number'} = "";
        $values{'area_number'} = trim($worksheet->get_cell($row, $types{'area_number'})->unformatted()) if(defined($worksheet->get_cell($row, $types{'area_number'})) && trim($worksheet->get_cell($row, $types{'area_number'})->unformatted()) ne "");
        
        $values{'district_name'} = "";
        $values{'district_name'} = trim($worksheet->get_cell($row, $types{'district_name'})->unformatted()) if(defined($worksheet->get_cell($row, $types{'district_name'})) && trim($worksheet->get_cell($row, $types{'district_name'})->unformatted()) ne "");
        
        $values{'district_number'} = "";
        $values{'district_number'} = trim($worksheet->get_cell($row, $types{'district_number'})->unformatted()) if(defined($worksheet->get_cell($row, $types{'district_number'})) && trim($worksheet->get_cell($row, $types{'district_number'})->unformatted()) ne "");
        
        $values{'td'} = "";
        $values{'td'} = trim($worksheet->get_cell($row, $types{'td'})->unformatted()) if(defined($worksheet->get_cell($row, $types{'td'})) && trim($worksheet->get_cell($row, $types{'td'})->unformatted()) ne "");
        
        $values{'lease_name'} = "";
        $values{'lease_name'} = trim($worksheet->get_cell($row, $types{'lease_name'})->unformatted()) if(defined($worksheet->get_cell($row, $types{'lease_name'})) && trim($worksheet->get_cell($row, $types{'lease_name'})->unformatted()) ne "");
        
        #Initalize SQL statements
        $update = "data_rigs_smith_files WHERE ";
        $condition = "data_rigs_smith_files WHERE ";
        $query = "data_rigs_smith_files SET ";
        
        #Data print and SQL statement creating loop
        foreach $key (sort keys %values)
        {
            print $key." => ".$values{$key}."\n";
            
            $update .= $key." = ".$dbh->quote($values{$key})." AND " if($key eq "rigid" || $key eq "company");
            $condition .= $key." = ".$dbh->quote($values{$key})." AND ";
            $query .= $key." = ".$dbh->quote($values{$key}).", ";
        }
        
        $update =~ s/ AND $//;
        $condition =~ s/ AND $//;
        $query =~ s/, $//;
        print "\n";   
    }#End of non header conditional
}#End of file rows loop

exit(0);