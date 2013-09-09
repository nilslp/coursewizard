#!/usr/bin/perl -w

use strict;
use Spreadsheet::ParseExcel;


my $file     = $ARGV[0];
my $password = $ARGV[1];
my $parser   = Spreadsheet::ParseExcel->new(Password =>$password);
my $workbook = $parser->parse($file);

if ( !defined $workbook ) {
    exit 1;
}
unless(open (MYFILE, '>/www2/sites/norfolk.learningpool.com/wwwroot/local/lp_norfolk_imports/data.txt')){
    exit 1;
}

for my $worksheet ( $workbook->worksheets() ) {

    my ( $row_min, $row_max ) = $worksheet->row_range();
    my ( $col_min, $col_max ) = $worksheet->col_range();

    #we need to break from this iteration if there are not many rows because it means there's no proper data on this worksheet
    if($row_max < 1000){
        next;
    }
    for my $row ( $row_min .. $row_max ) {
        my @array;
        
        for my $col ( $col_min .. $col_max ) {
            my $val;
            
            my $cell = $worksheet->get_cell( $row, $col );
            if($cell){
                $val = $cell->value();
                if(index($val, ',') != -1){
                    $val = '"'.$val.'"';
                }
            }else{
                #must be blank...
                $val = '';
            }
            push(@array, $val);
            
        }
        my $string = join(',', @array);
        print MYFILE $string ;
        print MYFILE "\n";
    }
}
close (MYFILE);
exit 0;