<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Export\PaymentDateCsvExport;
use App\Services\PayrollDateGenerator;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Console\Command;

use function Laravel\Prompts\text;

class GeneratePayrollDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-payroll-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function __construct(private PayrollDateGenerator $payrollDateGenerator, private PaymentDateCsvExport $paymentDateCsvExport)
    {
        parent::__construct();

    }
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting Payroll Date generation.");
        
        // @todo, listen for cli args startdate, enddate and filename. 
        // For now we ask nicely for input. and we create the filename based on the dateranges.

        $fromDateInput = text(
            label: 'What is the start date?',
            placeholder: 'E.g. 2025-01-01',
            default: '2025-01-01',
            hint: 'Please insert a valid date in year, month and day format'
        );

        $endDateInput = text(
            label: 'What is the start date?',
            placeholder: 'E.g. 2025-12-31',
            default: '2025-12-31',
            hint: 'Please insert a valid date in year, month and day format'
        );

        try{
            $fromDate = new Carbon($fromDateInput);
            $endDate = new Carbon($endDateInput);
        }
        catch(InvalidFormatException $e){
            $this->error("Invalid date or dates where inserted.");
            return Command::FAILURE;
        }
        
        $this->info(sprintf("Generating Payroll dates from %s to %s", $fromDate->toString(), $endDate->toString()));

        $fileName = sprintf("payrolldates_%s_%s.csv", $fromDate->format("Y-m-d"), $endDate->format("Y-m-d") );
        
        $this->payrollDateGenerator->initialize($fromDate, $endDate);

        $this->paymentDateCsvExport->generateCsv($fileName, $this->payrollDateGenerator->flattenedDates());

        $this->info("Payroll file generated, can be found here: " . $fileName);

        return Command::SUCCESS;
    }
}
