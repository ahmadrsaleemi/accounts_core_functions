<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Sale;
use App\Expense;
use App\Ledger;
use Carbon\Carbon;
use App\Stichinv;
use DB;
use App\Employee;
use App\Manufacture;
use App\Payrolladvance;
use App\Bonus;
use App\Originalsale;
use App\Manufacturedetail;
use App\Customer;
class ReportController extends Controller
{
    Public function __construct(){
         //check if user lgged in
        $this->middleware('auth');
    }
    
    
    public function index()
    {

        return view('reports/view');
    }
    public function dailycashsalereport()
    {
        $query = "";
        $query = DB::raw('SELECT sales.*,customers.customer_name from sales LEFT JOIN customers on sales.customer=customers.customer_id where date(sales.date) =CURDATE()');
        $sales = DB::select($query);
        // $original_sale_query = DB::raw('SELECT originalsales.*,customers.customer_name from originalsales LEFT JOIN customers on originalsales.customer=customers.customer_id where date(originalsales.date) =CURDATE()');
        // $original_sale = DB::select($original_sale_query);
        // // $sales = Sale::leftjoin('customers','customers.customer_id','sales.customer')->whereDate('sales.date', Carbon::today())->where('sales.credit_sales',0)->get();
        // $sales[] = $original_sale;
        return $sales;
    }

    public function weeklycashsalereport()
    {
        $query = "";
        $query = DB::raw('SELECT sales.*,customers.customer_name from sales LEFT JOIN customers on sales.customer=customers.customer_id where YEARWEEK(sales.date) = YEARWEEK(CURDATE())');
        $weeklysales = DB::select($query);
        // Carbon::setWeekStartsAt(Carbon::SUNDAY);

        // $weeklysales  = Sale::leftjoin('customers','customers.customer_id','sales.customer')->whereBetween('sales.created_at', [Carbon::now()->startOfWeek(),Carbon::now()->endOfWeek()])->where('sales.credit_sales',0)->get();
        return $weeklysales;
    }

    public function monthlycashsalereport()
    {
        $query = "";
        $query = DB::raw('SELECT sales.*,customers.customer_name from sales LEFT JOIN customers on sales.customer=customers.customer_id where MONTH(sales.date) = MONTH(CURRENT_DATE())');
        $monthlysales = DB::select($query);
        return $monthlysales;
    }

    public function datecashsalereport(Request $request)
    {
        if ($request->fromdate != null && $request->todate) {
            $query = "";
            $query = DB::raw('SELECT sales.*,customers.customer_name from sales LEFT JOIN customers on sales.customer=customers.customer_id where date(sales.date) >= "' . $request->fromdate . '" AND date(sales.date) <= "' . $request->todate . '"');
            $datecashsales = DB::select($query);

            // $datecashsales  = Sale::leftjoin('customers','customers.customer_id','sales.customer')->whereBetween('sales.created_at',[$request->fromdate,$request->todate])->where('sales.credit_sales',0)->get();

            if ($datecashsales != null) {
                return $datecashsales;
            }
        }
    }

    public function dailycashsales()
    {

        $query = "";
        $query = DB::raw('SELECT sales.*,customers.customer_name from sales LEFT JOIN customers on sales.customer=customers.customer_id where MONTH(sales.date) = MONTH(CURRENT_DATE())');
        $sales = DB::select($query);
        return view('reports/cashsales', compact('sales'));
    }

    public function dailyexpensereport()
    {
        $query = "";
        $query = DB::raw('SELECT expenses.*,chartofaccounts.coa_title from expenses LEFT JOIN chartofaccounts on expenses.coa_id=chartofaccounts.coa_id where date(expenses.date) =CURDATE()');
        $expenses = DB::select($query);
        // $expenses = Expense::leftjoin('chartofaccounts','expenses.coa_id','=','chartofaccounts.coa_id')->whereDate('expenses.date', Carbon::today())->get();
        return $expenses;
    }

    public function weeklybalancereport()
    {
        $query = "";
        $query = DB::raw('SELECT expenses.*,chartofaccounts.coa_title from expenses LEFT JOIN chartofaccounts on expenses.coa_id=chartofaccounts.coa_id where YEARWEEK(expenses.date) = YEARWEEK(CURDATE())');
        $weeklyexpenses = DB::select($query);
        //  Carbon::setWeekStartsAt(Carbon::SUNDAY);
        // $weeklyexpenses  = Expense::leftjoin('chartofaccounts','expenses.coa_id','=','chartofaccounts.coa_id')->whereBetween('expenses.date', [Carbon::now()->startOfWeek(),Carbon::now()->endOfWeek()])->get();
        return  $weeklyexpenses;
    }

    public function monthlybalancereport()
    {
        $query = "";
        $query = DB::raw('SELECT expenses.*,chartofaccounts.coa_title from expenses LEFT JOIN chartofaccounts on expenses.coa_id=chartofaccounts.coa_id where MONTH(expenses.date) = MONTH(CURRENT_DATE())');
        $monthlyexpenses = DB::select($query);
        // $currentMonth = date('m');
        // $monthlyexpenses =Expense::leftjoin('chartofaccounts','expenses.coa_id','=','chartofaccounts.coa_id')->whereRaw('MONTH(expenses.date) = ?',[$currentMonth])->get();
        return $monthlyexpenses;
    }
    public function dailyexpenses()
    {
        $query = DB::raw('SELECT expenses.*,chartofaccounts.coa_title from expenses LEFT JOIN chartofaccounts on expenses.coa_id=chartofaccounts.coa_id where MONTH(expenses.date) = MONTH(CURRENT_DATE())');
        $expenses = DB::select($query);
        return view('reports/expenses', compact('expenses'));
    }

    public function dateexpensereport(Request $request)
    {

        if ($request->fromdate != null && $request->todate) {
            $query = "";
            $query = DB::raw('SELECT expenses.*,chartofaccounts.coa_title from expenses LEFT JOIN chartofaccounts on expenses.coa_id=chartofaccounts.coa_id where date(expenses.date) in ("' . $request->fromdate . '","' . $request->todate . '")');
            $dateexpense = DB::select($query);
            // $dateexpense  = Expense::leftjoin('chartofaccounts','expenses.coa_id','=','chartofaccounts.coa_id')->whereBetween('expenses.date',[$request->fromdate,$request->todate])->get();
            if ($dateexpense != null) {
                return $dateexpense;
            }
        }
    }
    public function plreport()
    {

        // $incomes=0;
        // $credit_sales=0;
        // $cash_sales=0;
        // $costofsales=0;
        // $expenses=0;
        // $query=0;
        // $query2=0;
        // $cosquery="";
        // $totalcos=0;
        // $currentMonth = date('m');

        // $query=DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=4 && x.account_id !=10101 && x.date > DATE_SUB(NOW(), INTERVAL 1 MONTH) GROUP BY x.account_id ORDER BY x.account_type ASC');
        // $incomes =DB::select($query);

        // $credit_sales=Stichinv::whereRaw('MONTH(date) = ?',[$currentMonth])->sum('total');

        //  $cosquery=DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date asc) x  where x.account_id="10102"  &&  MONTH(x.date) ='.date("m").'');

        //  $costofsales=DB::select($cosquery);
        //  foreach ($costofsales as $cos) {
        //       $totalcos =$cos->balance;
        //  }

        //  $query2=DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=5 && account_id NOT LIKE "%ade_%" && account_id !="10102" &&  x.date > DATE_SUB(NOW(), INTERVAL 1 MONTH) GROUP BY x.account_id');
        // $expenses=DB::select($query2);


        $incomes = 0;
        $credit_sales = 0;
        $cash_sales = 0;
        $costofsales = 0;
        $expenses = 0;
        $query = 0;
        $query2 = 0;
        $cosquery = "";
        $totalcos = 0;
        $currentMonth = date('m');
        $sales = 0;
        $sale = 0;
        $query = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=4 && x.account_id !=10101  GROUP BY x.account_id ORDER BY x.account_type ASC');
        $incomes = DB::select($query);

        $salequery = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date asc) x  where x.account_id="10101" ');
        $sales = DB::select($salequery);
        foreach ($sales as $sale) {
            $credit_sales = $sale->balance;
        }

        // $credit_sales=Stichinv::sum('total');

        $cosquery = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date asc) x  where x.account_id="10102" ');

        $costofsales = DB::select($cosquery);
        foreach ($costofsales as $cos) {
            $totalcos = $cos->balance;
        }

        $query2 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=5 && account_id NOT LIKE "%ade_%" && account_id !="10102"  GROUP BY x.account_id');
        $expenses = DB::select($query2);
        return view('reports/plreport', compact('incomes', 'cash_sales', 'credit_sales', 'totalcos', 'expenses'));
    }

    public function dailyplreport()
    {
        $incomes = 0;
        $credit_sales = 0;
        $cash_sales = 0;
        $costofsales = 0;
        $expenses = 0;
        $query = 0;
        $query2 = 0;
        $cosquery = "";
        $totalcos = 0;
        $query = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=4 && x.account_id !=10101 && DATE(x.date) = CURDATE() GROUP BY x.account_id ORDER BY x.account_type ASC');
        $incomes = DB::select($query);

        $credit_sales = Stichinv::whereDate('date', Carbon::today())->sum('total');
        $cosquery = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x  where x.account_id="10102" && DATE(x.date) = CURDATE() GROUP BY x.account_id ORDER BY x.account_type ASC');
        $costofsales = DB::select($cosquery);
        foreach ($costofsales as $cos) {
            $totalcos = $cos->balance;
        }
        // $costofsales=Ledger::where('account_id','%product_%')->whereDate('date', Carbon::today())->sum('credit_ammount');
        $query2 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=5 && x.account_id !=10102 && DATE(x.date) = CURDATE() GROUP BY x.account_id GROUP BY x.account_id');
        $expenses = DB::select($query2);
        $total_income = 0;
        $total_expenses = 0;
        echo '<h3>Daily Profit And Loss Report</h3><hr><h5>Income</h5>
        <table class="table table-hover" style="width:100%;text-align:left;">';

        foreach ($incomes as $income) {
            echo '<tr><td>' . $income->coa_title . '</td><td>' . $income->balance . '</td></tr>';
            $total_income += $income->balance;
        }
        echo '<tr><th>Total Income</th><td><b>' . $total_income . '</b></td></tr>
        </table>
        <h5>Sales</h5>
        <table class="table table-hover" style="width:100%;text-align:left;">
            <tr><td>Sales</td><td>' . $credit_sales . '</td></tr>
            <tr><th>Total Sales</th><td><b>' . ($credit_sales) . '</b></td></tr>
        </table>
        <h5>Revenue And Grossprofit</h5>
        
        <table class="table table-hover" style="width:100%;text-align:left;">
            <tr><td>Revenue</td><td>';
        echo $credit_sales + $total_income;
        echo '</td></tr>
            <tr><td>Cost Of Sales</td><td>';
        echo $totalcos;
        echo '</td></tr>
            <tr><th>Gross Profit</th><td><b>';
        $credit_sales + $total_income - $totalcos;
        echo '</b></td></tr>
        </table>
        <h5>Expense</h5>
        <table class="table table-hover" style="width:100%;text-align:left;">
            <tr><th>Expence</th><td></td></tr>';
        foreach ($expenses as $expense) {
            echo '<tr><td>' . $expense->coa_title . '</td><td>' . $expense->balance . '</td>';
            $total_expenses = $expense->balance;
        }
        echo '<tr><th>Total Expense</th><td><b>' . $total_expenses . '</b></td></tr>
        </tr>
        </table>
        <h5>Net Profit</h5>
        <table class="table table-hover" style="width:100%;text-align:left;">
            <tr><th>Net Profit</th><td><b>';
        echo $credit_sales + $total_income - $totalcos - $total_expenses . "&nbsp/- Grossprofit-Expense";
        echo '</b></td></tr>
        </table>';
    }

    public function weeklyplreport()
    {
        $incomes = 0;
        $credit_sales = 0;
        $cash_sales = 0;
        $costofsales = 0;
        $expenses = 0;
        $query = 0;
        $query2 = 0;
        $cosquery = "";
        $totalcos = 0;
        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        Carbon::setWeekEndsAt(Carbon::SATURDAY);


        $query = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=4 && x.account_id !=10101 && YEARWEEK(x.date, 1) = YEARWEEK(CURDATE(), 1) GROUP BY x.account_id ORDER BY x.account_type ASC');
        $incomes = DB::select($query);
        $credit_sales = Stichinv::whereBetween('date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('total');


        $cosquery = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x  where x.account_id="10102" && YEARWEEK(x.date, 1) = YEARWEEK(CURDATE(), 1) GROUP BY x.account_id ORDER BY x.account_type ASC');
        $costofsales = DB::select($cosquery);
        foreach ($costofsales as $cos) {
            $totalcos = $cos->balance;
        }


        $query2 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=5 && account_id NOT LIKE "%ade_%" && account_id !="10102" && YEARWEEK(x.date, 1) GROUP BY x.account_id');

        $expenses = DB::select($query2);
        $total_income = 0;
        $total_expenses = 0;
        echo '<h3>Weekly Profit And Loss Report</h3><hr><h5>Income</h5>
        <table class="table table-hover" style="width:100%;text-align:left;">';

        foreach ($incomes as $income) {
            echo '<tr><td>' . $income->coa_title . '</td><td>' . $income->balance . '</td></tr>';
            $total_income += $income->balance;
        }
        echo '<tr><th>Total Income</th><td><b>' . $total_income . '</b></td></tr>
        </table>
        <h5>Sales</h5>
        <table class="table table-hover" style="width:100%;text-align:left;">
            <tr><td>Sales</td><td>' . $credit_sales . '</td></tr>
            <tr><th>Total Sales</th><td><b>' . ($credit_sales) . '</b></td></tr>
        </table>
        <h5>Revenue And Grossprofit</h5>
        <table class="table table-hover" style="width:100%;text-align:left;">
            <tr><td>Revenue</td><td>';
        echo $credit_sales + $total_income;
        echo '</td></tr>
            <tr><td>Cost Of Sales</td><td>';
        echo  $totalcos;
        echo '</td></tr>
            <tr><th>Gross Profit</th><td><b>';
        echo $credit_sales + $total_income - $totalcos;
        echo '</b></td></tr>
        </table>
        <h5>Expense</h5>
        <table class="table table-hover" style="width:100%;text-align:left;">
            <tr><th>Expence</th><td></td></tr>';
        foreach ($expenses as $expense) {
            echo '<tr><td>' . $expense->coa_title . '</td><td>' . $expense->balance . '</td>';
            $total_expenses += $expense->balance;
        }
        echo '<tr><th>Total Expense</th><td><b>' . $total_expenses . '</b></td></tr>
        </tr>
        </table>
        <h5>Net Profit</h5>
        <table class="table table-hover" style="width:100%;text-align:left;">
            <tr><th>Net Profit</th><td><b>';
        echo $credit_sales + $total_income - $totalcos - $total_expenses . "&nbsp/- Grossprofit-Expense";
        echo '</b></td></tr>
        </table>';
    }
    public function monthlyplreport()
    {
        $incomes = 0;
        $credit_sales = 0;
        $cash_sales = 0;
        $costofsales = 0;
        $expenses = 0;
        $query = 0;
        $query2 = 0;
        $cosquery = "";
        $totalcos = 0;
        $currentMonth = date('m');

        $query = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=4 && x.account_id !=10101 && x.date > DATE_SUB(NOW(), INTERVAL 1 MONTH) GROUP BY x.account_id ORDER BY x.account_type ASC');
        $incomes = DB::select($query);

        $credit_sales = Stichinv::whereRaw('MONTH(date) = ?', [$currentMonth])->sum('total');

        $cosquery = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date asc) x  where x.account_id="10102"  &&  MONTH(x.date) =' . date("m") . '');

        $costofsales = DB::select($cosquery);
        foreach ($costofsales as $cos) {
            $totalcos = $cos->balance;
        }

        $query2 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=5 && account_id NOT LIKE "%ade_%" && account_id !="10102" &&  x.date > DATE_SUB(NOW(), INTERVAL 1 MONTH) GROUP BY x.account_id');
        $expenses = DB::select($query2);
        $total_income = 0;
        $total_expenses = 0;
        echo '<h3>Monthly Profit And Loss Report</h3><hr><h5>Income</h5>
        <table class="table table-hover" style="width:100%;text-align:left;">';

        foreach ($incomes as $income) {
            echo '<tr><td>' . $income->coa_title . '</td><td>' . $income->balance . '</td></tr>';
            $total_income += $income->balance;
        }
        echo '<tr><th>Total Income</th><td><b>' . $total_income . '</b></td></tr>
        </table>
        <h5>Sales</h5>
        <table class="table table-hover" style="width:100%;text-align:left;">
            <tr><td>Sales</td><td>' . $credit_sales . '</td></tr>
            <tr><th>Total Sales</th><td><b>' . ($credit_sales) . '</b></td></tr>
        </table>
        <h5>Revenue And Grossprofit</h5>
        <table class="table table-hover" style="width:100%;text-align:left;">
            <tr><td>Revenue</td><td>';
        echo $credit_sales + $total_income;
        echo '</td></tr>
            <tr><td>Cost Of Sales</td><td>';
        echo $totalcos;
        echo '</td></tr>
            <tr><th>Gross Profit</th><td><b>';
        echo $credit_sales + $total_income - $totalcos;
        echo '</b></td></tr>
        </table>
        <h5>Expense</h5>
        <table class="table table-hover" style="width:100%;text-align:left;">
            <tr><th>Expence</th><td></td></tr>';
        foreach ($expenses as $expense) {
            echo '<tr><td>' . $expense->coa_title . '</td><td>' . $expense->balance . '</td>';
            $total_expenses += $expense->balance;
        }
        echo '<tr><th>Total Expense</th><td><b>' . $total_expenses . '</b></td></tr>
        </tr>
        </table>
        <h5>Net Profit</h5>
        <table class="table table-hover" style="width:100%;text-align:left;">
            <tr><th>Net Profit</th><td><b>';
        echo $credit_sales + $total_income - $totalcos - $total_expenses . "&nbsp/- Grossprofit-Expense";
        echo '</b></td></tr>
        </table>';
    }

    public function datetodatepl(Request $request)
    {
        $incomes = 0;
        $credit_sales = 0;
        $cash_sales = 0;
        $costofsales = 0;
        $expenses = 0;
        $query = 0;
        $query2 = 0;
        $cosquery = "";
        $totalcos = 0;
        $currentMonth = date('m');
        $sales = 0;
        $sale = 0;
        $query = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where date(x.date) <= "' . date($request->todate) . '" && x.account_type=4 && x.account_id !=10101  GROUP BY x.account_id ORDER BY x.account_type ASC');
        $incomes = DB::select($query);

        $salequery = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date asc) x  where date(x.date) <= "' . date($request->todate) . '" && x.account_id="10101" ');
        $sales = DB::select($salequery);
        foreach ($sales as $sale) {
            $credit_sales = $sale->balance;
        }

        // $credit_sales=Stichinv::sum('total');

        $cosquery = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date asc) x   where date(x.date) <= "' . date($request->todate) . '" && x.account_id="10102" ');

        $costofsales = DB::select($cosquery);
        foreach ($costofsales as $cos) {
            $totalcos = $cos->balance;
        }

        $query2 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where date(x.date) <= "' . date($request->todate) . '" && x.account_type=5 && account_id NOT LIKE "%ade_%" && account_id !="10102"  GROUP BY x.account_id');
        $expenses = DB::select($query2);
        $total_income = 0;
        $total_expenses = 0;
        echo '<h3>To ' . $request->todate . ' Profit And Loss Report</h3><hr><h5>Income</h5>
        <table class="table table-hover" style="width:100%;text-align:left;">';

        foreach ($incomes as $income) {
            echo '<tr><td>' . $income->coa_title . '</td><td>' . $income->balance . '</td></tr>';
            $total_income += $income->balance;
        }
        echo '<tr><th>Total Income</th><td><b>' . $total_income . '</b></td></tr>
        </table>
        <h5>Sales</h5>
        <table class="table table-hover" style="width:100%;text-align:left;">
            <tr><td>Sales</td><td>' . $credit_sales . '</td></tr>
            <tr><th>Total Sales</th><td><b>' . ($credit_sales) . '</b></td></tr>
        </table>
        <h5>Revenue And Grossprofit</h5>
        <table class="table table-hover">
            <tr><td>Revenue</td><td>';
        echo $credit_sales + $total_income;
        echo '</td></tr>
            <tr><td>Cost Of Sales</td><td>';
        echo  $totalcos;
        echo '</td></tr>
            <tr><th>Gross Profit</th><td><b>';
        echo $credit_sales + $total_income - $totalcos;
        echo '</b></td></tr>
        </table>
        <h5>Expense</h5>
        <table class="table table-hover" style="width:100%;text-align:left;">
            <tr><th>Expence</th><td></td></tr>';
        foreach ($expenses as $expense) {
            echo '<tr><td>' . $expense->coa_title . '</td><td>' . $expense->balance . '</td>';
            $total_expenses += $expense->balance;
        }
        echo '<tr><th>Total Expense</th><td><b>' . $total_expenses . '</b></td></tr>
        </tr>
        </table>
        <h5>Net Profit</h5>
        <table class="table table-hover" style="width:100%;text-align:left;">
            <tr><th>Net Profit</th><td><b>';
        echo $credit_sales + $total_income - $totalcos - $total_expenses . "&nbsp/- Grossprofit-Expense";
        echo '</b></td></tr>
        </table>';
    }

    public function balancesheetreport()
    {
        $netprofit = 0;
        $sales = 0;
        $query = "";
        $query2 = "";
        $query3 = "";
        $query4 = "";
        $invtotal = 0;
        $queryy = "";
        $incomes = 0;
        $credit_sales = 0;
        $cash_sales = 0;
        $costofsales = 0;
        $expenses = 0;
        $cosquery = "";
        $totalcos = 0;
        $total_income = 0;
        $total_expenses = 0;
        $currentMonth = date('m');
        $query = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=4 && x.account_id !=10101  GROUP BY x.account_id ORDER BY x.account_type ASC');
        $incomes = DB::select($query);

        // $credit_sales=Stichinv::sum('total');
        $salequery = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date asc) x  where x.account_id="10101" ');
        $sales = DB::select($salequery);
        foreach ($sales as $sale) {
            $credit_sales = $sale->balance;
        }
        $cosquery = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date asc) x  where x.account_id="10102" ');

        $costofsales = DB::select($cosquery);
        foreach ($costofsales as $cos) {
            $totalcos = $cos->balance;
        }

        $query2 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=5 && account_id NOT LIKE "%ade_%" && account_id !="10102"  GROUP BY x.account_id');
        $expenses = DB::select($query2);

        foreach ($expenses as $expense) {
            $total_expenses += $expense->balance;
        }
        $netprofit = $credit_sales - $totalcos - $total_expenses;

        $query = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=1  OR x.account_type=6   GROUP BY account_id ORDER BY x.account_type ASC');
        $assets = DB::select($query);
        $query3 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=3  GROUP BY account_id ORDER BY x.account_type ASC');
        $capitals = DB::select($query3);

        $query4 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.credit_ammount,ledgers.debit_ammount,ledgers.account_id,ledgers.transection_type,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=2  GROUP BY account_id ORDER BY x.account_type ASC');
        $liabilities = DB::select($query4);

        $query2 = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x where account_id LIKE "%product_%"   ORDER BY x.account_type ASC');
        $inv_total = DB::select($query2);
        foreach ($inv_total as $invto) {
            $invtotal += $invto->balance;
        }
        $queryy = DB::raw('SELECT x.*,chartofaccounts.*,fixedacounts.amo FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id LEFT JOIN fixedacounts on fixedacounts.ass_name=chartofaccounts.coa_description where x.account_type=5 && x.account_id LIKE "de_%"  GROUP BY account_id ORDER BY x.account_type ASC');
        $acu_expences = DB::select($queryy);
        return view('reports/balancesheet', compact('assets', 'invtotal', 'capitals', 'liabilities', 'netprofit', 'acu_expences'));
    }

    public function weeklybalancesheet()
    {

        $netprofit = 0;
        $sales = 0;
        $query = "";
        $query2 = "";
        $query3 = "";
        $query4 = "";
        $invtotal = 0;
        $queryy = "";
        $incomes = 0;
        $credit_sales = 0;
        $cash_sales = 0;
        $costofsales = 0;
        $expenses = 0;
        $cosquery = "";
        $totalcos = 0;
        $total_income = 0;
        $total_expenses = 0;
        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        Carbon::setWeekEndsAt(Carbon::SATURDAY);


        $query = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=4 && x.account_id !=10101 && YEARWEEK(x.date, 1) = YEARWEEK(CURDATE(), 1) GROUP BY x.account_id ORDER BY x.account_type ASC');
        $incomes = DB::select($query);

        $credit_sales = Stichinv::whereBetween('date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('total');
        $cosquery = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x  where x.account_id="10102" && YEARWEEK(x.date, 1) = YEARWEEK(CURDATE(), 1) GROUP BY x.account_id ORDER BY x.account_type ASC');
        $costofsales = DB::select($cosquery);
        foreach ($costofsales as $cos) {
            $totalcos = $cos->balance;
        }


        $query2 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=5 && account_id NOT LIKE "%ade_%" && account_id !="10102" && YEARWEEK(x.date, 1) GROUP BY x.account_id');

        $expenses = DB::select($query2);
        foreach ($expenses as $expense) {
            $total_expenses += $expense->balance;
        }
        $netprofit = $credit_sales + $total_income - $totalcos - $total_expenses;
        $query = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=4 && x.account_id !=10101 && YEARWEEK(x.date, 1) = YEARWEEK(CURDATE(), 1) GROUP BY x.account_id ORDER BY x.account_type ASC');
        $incomes = DB::select($query);

        $credit_sales = Stichinv::whereBetween('date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('total');
        $cosquery = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x  where x.account_id="10102" && YEARWEEK(x.date, 1) = YEARWEEK(CURDATE(), 1) GROUP BY x.account_id ORDER BY x.account_type ASC');
        $costofsales = DB::select($cosquery);
        foreach ($costofsales as $cos) {
            $totalcos = $cos->balance;
        }


        $query2 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=5 && account_id NOT LIKE "%ade_%" && account_id !="10102" && YEARWEEK(x.date, 1) GROUP BY x.account_id');

        $expenses = DB::select($query2);
        foreach ($expenses as $expense) {
            $total_expenses += $expense->balance;
        }
        $netprofit = $credit_sales + $total_income - $totalcos - $total_expenses;
        echo '<hr><h5>Weekly Balancesheet</h5><table class="table table-hover" style="width:100%;text-align:left;">
    <tr><th>Assets</th><td></td></tr>';
        foreach ($assets as $asset) {
            if ($asset->balance > 0) {
                echo '<tr><td>' . $asset->coa_title . '</td><td>' . number_format(round($asset->balance)) . '</td></tr>';
                $asset_total += round($asset->balance);
            }
        }
        foreach ($liabilities as $liability) {
            if ($liability->balance < 0) {
                if ($liability->transection_type != '10') {
                    echo '<tr><td>' . $liability->coa_title . '</td><td>' . number_format(round($liability->balance * -1)) . '</td></tr>';
                }
                $asset_total += round($liability->balance * -1);
            }
        }
        echo '<tr><th>Inventories Total</th><th> ' . round($invtotal) . ' </th></tr>
    <tr><th>Sub Assets Total</th><th> ' . round($asset_total) . ' </th></tr>
    <tr><th>Accumulative Expenses</th><td></td></tr>';
        foreach ($acu_expences as $acu_expence) {
            if ($acu_expence->balance > 0)
                echo '<tr><td>' . $acu_expence->coa_title . '</td><td>' . number_format(round($acu_expence->balance)) . '</td></tr>';
            $acu_expence_total += round($acu_expence->balance);
        }

        echo '<tr><th>Accumulative Total</th><th> ' . number_format(round($acu_expence_total)) . ' </th></tr>
    <tr><th>Assets Total</th><th> ' . round($asset_total - $acu_expence_total) . ' </th></tr>
    <tr><th>Capital Account</th><td></td></tr>';
        foreach ($capitals as $capital) {
            echo '<tr><td>' . $capital->coa_title . '</td><td>' . number_format(round($capital->balance)) . '</td></tr>';
            $capital_total += round($capital->balance);
        }

        echo '<tr><th>Capital Total</th><th> ' . number_format(round($capital_total)) . ' </th></tr>

    <tr><th>Net Profit</th><td><b>' . number_format(round($netprofit)) . '</b></td></tr>
    <tr><th>liability Account</th><td></td></tr>';
        foreach ($liabilities as $liability) {

            if ($liability->transection_type != '10') {
                if ($liability->balance > 0) {
                    echo '<tr><td>' . $liability->coa_title . '</td><td>' . number_format(round($liability->balance)) . '</td></tr>';
                }
            }
            if ($liability->transection_type == '10') {
                $employee_liability += round($liability->balance);
            }

            $liability_total += round($liability->balance);
        }
        foreach ($assets as $asset) {
            if ($asset->balance < 0) {
                echo '<tr><td>' . $asset->coa_title . '</td><td>' . number_format(round($asset->balance * -1)) . '</td></tr>';
                $liability_total += round($asset->balance * -1);
            }
        }
        echo '<tr><th>Outstanding Wages</th><th> ' . number_format(round($employee_liability)) . ' </th></tr><tr><th>Liability Total</th><th> ' . number_format(round($liability_total)) . ' </th></tr>
    <tr><th>Final Total</th><th>' . number_format(round($capital_total + $netprofit + $liability_total)) . '</th></tr>

</table>';
    }
    public function dailybalancesheet()
    {
        $netprofit = 0;
        $sales = 0;
        $query = "";
        $query2 = "";
        $query3 = "";
        $query4 = "";
        $invtotal = 0;
        $queryy = "";
        $incomes = 0;
        $credit_sales = 0;
        $cash_sales = 0;
        $costofsales = 0;
        $expenses = 0;
        $cosquery = "";
        $totalcos = 0;
        $total_income = 0;
        $total_expenses = 0;
        $query = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=4 && x.account_id !=10101 && DATE(x.date) = CURDATE() GROUP BY x.account_id ORDER BY x.account_type ASC');
        $incomes = DB::select($query);

        $credit_sales = Stichinv::whereDate('date', Carbon::today())->sum('total');
        $cosquery = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x  where x.account_id="10102" && DATE(x.date) = CURDATE() GROUP BY x.account_id ORDER BY x.account_type ASC');
        $costofsales = DB::select($cosquery);
        foreach ($costofsales as $cos) {
            $totalcos = $cos->balance;
        }
        // $costofsales=Ledger::where('account_id','%product_%')->whereDate('date', Carbon::today())->sum('credit_ammount');
        $query2 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=5 && x.account_id !=10102 && DATE(x.date) = CURDATE() GROUP BY x.account_id');
        $expenses = DB::select($query2);
        foreach ($expenses as $expense) {
            $total_expenses += $expense->balance;
        }
        $netprofit = $credit_sales + $total_income - $totalcos - $total_expenses;
        $query = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=1 && YEARWEEK(x.date, 1) = YEARWEEK(CURDATE(), 1) OR x.account_type=6 && DATE(x.date) = CURDATE()  GROUP BY account_id ORDER BY x.account_type ASC');
        $assets = DB::select($query);
        $query3 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=3 && DATE(x.date) = CURDATE() GROUP BY account_id ORDER BY x.account_type ASC');
        $capitals = DB::select($query3);

        $query4 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.transection_type,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=2 && DATE(x.date) = CURDATE() GROUP BY account_id ORDER BY x.account_type ASC');
        $liabilities = DB::select($query4);

        $query2 = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x where account_id LIKE "%product_%" && DATE(x.date) = CURDATE()  ORDER BY x.account_type ASC');
        $inv_total = DB::select($query2);
        foreach ($inv_total as $invto) {
            $invtotal += $invto->balance;
        }
        $queryy = DB::raw('SELECT x.*,chartofaccounts.*,fixedacounts.amo FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id LEFT JOIN fixedacounts on fixedacounts.ass_name=chartofaccounts.coa_description where x.account_type=5 && x.account_id LIKE "de_%" && DATE(x.date) = CURDATE() GROUP BY account_id ORDER BY x.account_type ASC');
        $acu_expences = DB::select($queryy);
        $asset_total = 0;
        $capital_total = 0;
        $liability_total = 0;
        $acu_expence_total = 0;
        $employee_liability = 0;
        echo '<hr><h5>Daily Balancesheet</h5><table class="table table-hover" style="width:100%;text-align:left;">
    <tr><th>Assets</th><td></td></tr>';
        foreach ($assets as $asset) {
            if ($asset->balance > 0) {
                echo '<tr><td>' . $asset->coa_title . '</td><td>' . number_format(round($asset->balance)) . '</td></tr>';
                $asset_total += round($asset->balance);
            }
        }
        foreach ($liabilities as $liability) {
            if ($liability->balance < 0) {
                if ($liability->transection_type != '10') {
                    echo '<tr><td>' . $liability->coa_title . '</td><td>' . number_format(round($liability->balance * -1)) . '</td></tr>';
                }
                $asset_total += round($liability->balance * -1);
            }
        }
        echo '<tr><th>Inventories Total</th><th> ' . round($invtotal) . ' </th></tr>
    <tr><th>Sub Assets Total</th><th> ' . round($asset_total) . ' </th></tr>
    <tr><th>Accumulative Expenses</th><td></td></tr>';
        foreach ($acu_expences as $acu_expence) {
            if ($acu_expence->balance > 0)
                echo '<tr><td>' . $acu_expence->coa_title . '</td><td>' . number_format(round($acu_expence->balance)) . '</td></tr>';
            $acu_expence_total += round($acu_expence->balance);
        }

        echo '<tr><th>Accumulative Total</th><th> ' . number_format(round($acu_expence_total)) . ' </th></tr>
    <tr><th>Assets Total</th><th> ' . round($asset_total - $acu_expence_total) . ' </th></tr>
    <tr><th>Capital Account</th><td></td></tr>';
        foreach ($capitals as $capital) {
            echo '<tr><td>' . $capital->coa_title . '</td><td>' . number_format(round($capital->balance)) . '</td></tr>';
            $capital_total += round($capital->balance);
        }

        echo '<tr><th>Capital Total</th><th> ' . number_format(round($capital_total)) . ' </th></tr>

    <tr><th>Net Profit</th><td><b>' . number_format(round($netprofit)) . '</b></td></tr>
    <tr><th>liability Account</th><td></td></tr>';
        foreach ($liabilities as $liability) {

            if ($liability->transection_type != '10') {
                if ($liability->balance > 0) {
                    echo '<tr><td>' . $liability->coa_title . '</td><td>' . number_format(round($liability->balance)) . '</td></tr>';
                }
            }
            if ($liability->transection_type == '10') {
                $employee_liability += round($liability->balance);
            }

            $liability_total += round($liability->balance);
        }
        foreach ($assets as $asset) {
            if ($asset->balance < 0) {
                echo '<tr><td>' . $asset->coa_title . '</td><td>' . number_format(round($asset->balance * -1)) . '</td></tr>';
                $liability_total += round($asset->balance * -1);
            }
        }
        echo '<tr><th>Outstanding Wages</th><th> ' . number_format(round($employee_liability)) . ' </th></tr><tr><th>Liability Total</th><th> ' . number_format(round($liability_total)) . ' </th></tr>
    <tr><th>Final Total</th><th>' . number_format(round($capital_total + $netprofit + $liability_total)) . '</th></tr>

</table>';
    }

    public function monthlybalancesheet()
    {
        $netprofit = 0;
        $sales = 0;
        $query = "";
        $query2 = "";
        $query3 = "";
        $query4 = "";
        $invtotal = 0;
        $queryy = "";
        $incomes = 0;
        $credit_sales = 0;
        $cash_sales = 0;
        $costofsales = 0;
        $expenses = 0;
        $cosquery = "";
        $totalcos = 0;
        $total_income = 0;
        $total_expenses = 0;
        $currentMonth = date('m');
        $query = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=4 && x.account_id !=10101 && Month(x.date) =' . date('m') . ' GROUP BY x.account_id ORDER BY x.account_type ASC');
        $incomes = DB::select($query);

        $credit_sales = Stichinv::whereRaw('MONTH(date) = ?', [$currentMonth])->sum('total');

        $cosquery = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date asc) x  where x.account_id="10102"  &&  MONTH(x.date) =' . date("m") . '');

        $costofsales = DB::select($cosquery);
        foreach ($costofsales as $cos) {
            $totalcos = $cos->balance;
        }

        $query2 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=5 && account_id NOT LIKE "%ade_%" && account_id !="10102" &&  Month(x.date) =' . date('m') . ' GROUP BY x.account_id');
        $expenses = DB::select($query2);
        foreach ($expenses as $expense) {
            $total_expenses += $expense->balance;
        }
        $netprofit = $credit_sales + $total_income - $totalcos - $total_expenses;

        $query = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=1 && Month(x.date) =' . date('m') . ' OR x.account_type=6 && Month(x.date) =' . date('m') . '  GROUP BY account_id ORDER BY x.account_type ASC');
        $assets = DB::select($query);
        $query3 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=3 && Month(x.date) =' . date('m') . ' GROUP BY account_id ORDER BY x.account_type ASC');
        $capitals = DB::select($query3);

        $query4 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date,ledgers.transection_type FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=2 && Month(x.date) =' . date('m') . ' GROUP BY account_id ORDER BY x.account_type ASC');
        $liabilities = DB::select($query4);

        $query2 = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x where account_id LIKE "%product_%" && Month(date) =' . date('m') . '  ORDER BY x.account_type ASC');
        $inv_total = DB::select($query2);
        foreach ($inv_total as $invto) {
            $invtotal += $invto->balance;
        }
        $queryy = DB::raw('SELECT x.*,chartofaccounts.*,fixedacounts.amo FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id LEFT JOIN fixedacounts on fixedacounts.ass_name=chartofaccounts.coa_description where x.account_type=5 && x.account_id LIKE "de_%" && Month(x.date) =' . date('m') . ' GROUP BY account_id ORDER BY x.account_type ASC');
        $acu_expences = DB::select($queryy);
        $asset_total = 0;
        $capital_total = 0;
        $liability_total = 0;
        $acu_expence_total = 0;
        $employee_liability = 0;
        echo '<hr><h5>Monthly Balancesheet</h5><table class="table table-hover" style="width:100%;text-align:left;">
    <tr><th>Assets</th><td></td></tr>';
        foreach ($assets as $asset) {
            if ($asset->balance > 0) {
                echo '<tr><td>' . $asset->coa_title . '</td><td>' . number_format(round($asset->balance)) . '</td></tr>';
                $asset_total += round($asset->balance);
            }
        }
        foreach ($liabilities as $liability) {
            if ($liability->balance < 0) {
                if ($liability->transection_type != '10') {
                    echo '<tr><td>' . $liability->coa_title . '</td><td>' . number_format(round($liability->balance * -1)) . '</td></tr>';
                }
                $asset_total += round($liability->balance * -1);
            }
        }
        echo '<tr><th>Inventories Total</th><th> ' . round($invtotal) . ' </th></tr>
    <tr><th>Sub Assets Total</th><th> ' . round($asset_total) . ' </th></tr>
    <tr><th>Accumulative Expenses</th><td></td></tr>';
        foreach ($acu_expences as $acu_expence) {
            if ($acu_expence->balance > 0)
                echo '<tr><td>' . $acu_expence->coa_title . '</td><td>' . number_format(round($acu_expence->balance)) . '</td></tr>';
            $acu_expence_total += round($acu_expence->balance);
        }

        echo '<tr><th>Accumulative Total</th><th> ' . number_format(round($acu_expence_total)) . ' </th></tr>
    <tr><th>Assets Total</th><th> ' . round($asset_total - $acu_expence_total) . ' </th></tr>
    <tr><th>Capital Account</th><td></td></tr>';
        foreach ($capitals as $capital) {
            echo '<tr><td>' . $capital->coa_title . '</td><td>' . number_format(round($capital->balance)) . '</td></tr>';
            $capital_total += round($capital->balance);
        }

        echo '<tr><th>Capital Total</th><th> ' . number_format(round($capital_total)) . ' </th></tr>

    <tr><th>Net Profit</th><td><b>' . number_format(round($netprofit)) . '</b></td></tr>
    <tr><th>liability Account</th><td></td></tr>';
        foreach ($liabilities as $liability) {

            if ($liability->transection_type != '10') {
                if ($liability->balance > 0) {
                    echo '<tr><td>' . $liability->coa_title . '</td><td>' . number_format(round($liability->balance)) . '</td></tr>';
                }
            }
            if ($liability->transection_type == '10') {
                $employee_liability += round($liability->balance);
            }

            $liability_total += round($liability->balance);
        }
        foreach ($assets as $asset) {
            if ($asset->balance < 0) {
                echo '<tr><td>' . $asset->coa_title . '</td><td>' . number_format(round($asset->balance * -1)) . '</td></tr>';
                $liability_total += round($asset->balance * -1);
            }
        }
        echo '<tr><th>Outstanding Wages</th><th> ' . number_format(round($employee_liability)) . ' </th></tr><tr><th>Liability Total</th><th> ' . number_format(round($liability_total)) . ' </th></tr>
    <tr><th>Final Total</th><th>' . number_format(round($capital_total + $netprofit + $liability_total)) . '</th></tr>

</table>';
    }

    public function datetodate(Request $request)
    {   
        $netprofit = 0;
        $sales = 0;
        $query = "";
        $query2 = "";
        $query3 = "";
        $query4 = "";
        $invtotal = 0;
        $queryy = "";
        $incomes = 0;
        $credit_sales = 0;
        $cash_sales = 0;
        $costofsales = 0;
        $expenses = 0;
        $cosquery = "";
        $totalcos = 0;
        $total_income = 0;
        $total_expenses = 0;
        $currentMonth = date('m');
        $query = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=4 && x.account_id !=10101  GROUP BY x.account_id ORDER BY x.account_type ASC');
        $incomes = DB::select($query);

        // $credit_sales=Stichinv::sum('total');
        $salequery = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date asc) x  where x.account_id="10101" ');
        $sales = DB::select($salequery);
        foreach ($sales as $sale) {
            $credit_sales = $sale->balance;
        }
        $cosquery = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date asc) x  where x.account_id="10102" ');

        $costofsales = DB::select($cosquery);
        foreach ($costofsales as $cos) {
            $totalcos = $cos->balance;
        }

        $query2 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=5 && account_id NOT LIKE "%ade_%" && account_id !="10102"  GROUP BY x.account_id');
        $expenses = DB::select($query2);

        foreach ($expenses as $expense) {
            $total_expenses += $expense->balance;
        }
        $netprofit = $credit_sales - $totalcos - $total_expenses;

        $query = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=1  OR x.account_type=6   GROUP BY account_id ORDER BY x.account_type ASC');
        $assets = DB::select($query);
        $query3 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=3  GROUP BY account_id ORDER BY x.account_type ASC');
        $capitals = DB::select($query3);

        $query4 = DB::raw('SELECT x.*,chartofaccounts.* FROM (SELECT DISTINCT ledgers.balance,ledgers.credit_ammount,ledgers.debit_ammount,ledgers.account_id,ledgers.transection_type,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_type=2  GROUP BY account_id ORDER BY x.account_type ASC');
        $liabilities = DB::select($query4);

        $query2 = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x where account_id LIKE "%product_%"   ORDER BY x.account_type ASC');
        $inv_total = DB::select($query2);
        foreach ($inv_total as $invto) {
            $invtotal += $invto->balance;
        }
        $queryy = DB::raw('SELECT x.*,chartofaccounts.*,fixedacounts.amo FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id LEFT JOIN fixedacounts on fixedacounts.ass_name=chartofaccounts.coa_description where x.account_type=5 && x.account_id LIKE "de_%"  GROUP BY account_id ORDER BY x.account_type ASC');
        $acu_expences = DB::select($queryy);
        $asset_total = 0;
        $capital_total = 0;
        $liability_total = 0;
        $acu_expence_total = 0;
        $employee_liability = 0;
        echo '<hr><h5>to '.$request->todate.' Balancesheet</h5><table class="table table-hover" style="width:100%;text-align:left;">
    <tr><th>Assets</th><td></td></tr>';
        foreach ($assets as $asset) {
            if ($asset->balance > 0) {
                echo '<tr><td>' . $asset->coa_title . '</td><td>' . number_format(round($asset->balance)) . '</td></tr>';
                $asset_total += round($asset->balance);
            }
        }
        foreach ($liabilities as $liability) {
            if ($liability->balance < 0) {
                if ($liability->transection_type != '10') {
                    echo '<tr><td>' . $liability->coa_title . '</td><td>' . number_format(round($liability->balance * -1)) . '</td></tr>';
                }
                $asset_total += round($liability->balance * -1);
            }
        }
        echo '<tr><th>Inventories Total</th><th> ' . round($invtotal) . ' </th></tr>
    <tr><th>Sub Assets Total</th><th> ' . round($asset_total) . ' </th></tr>
    <tr><th>Accumulative Expenses</th><td></td></tr>';
        foreach ($acu_expences as $acu_expence) {
            if ($acu_expence->balance > 0)
                echo '<tr><td>' . $acu_expence->coa_title . '</td><td>' . number_format(round($acu_expence->balance)) . '</td></tr>';
            $acu_expence_total += round($acu_expence->balance);
        }

        echo '<tr><th>Accumulative Total</th><th> ' . number_format(round($acu_expence_total)) . ' </th></tr>
    <tr><th>Assets Total</th><th> ' . round($asset_total - $acu_expence_total) . ' </th></tr>
    <tr><th>Capital Account</th><td></td></tr>';
        foreach ($capitals as $capital) {
            echo '<tr><td>' . $capital->coa_title . '</td><td>' . number_format(round($capital->balance)) . '</td></tr>';
            $capital_total += round($capital->balance);
        }

        echo '<tr><th>Capital Total</th><th> ' . number_format(round($capital_total)) . ' </th></tr>

    <tr><th>Net Profit</th><td><b>' . number_format(round($netprofit)) . '</b></td></tr>
    <tr><th>liability Account</th><td></td></tr>';
        foreach ($liabilities as $liability) {

            if ($liability->transection_type != '10') {
                if ($liability->balance > 0) {
                    echo '<tr><td>' . $liability->coa_title . '</td><td>' . number_format(round($liability->balance)) . '</td></tr>';
                }
            }
            if ($liability->transection_type == '10') {
                $employee_liability += round($liability->balance);
            }

            $liability_total += round($liability->balance);
        }
        foreach ($assets as $asset) {
            if ($asset->balance < 0) {
                echo '<tr><td>' . $asset->coa_title . '</td><td>' . number_format(round($asset->balance * -1)) . '</td></tr>';
                $liability_total += round($asset->balance * -1);
            }
        }
        echo '<tr><th>Outstanding Wages</th><th> ' . number_format(round($employee_liability)) . ' </th></tr><tr><th>Liability Total</th><th> ' . number_format(round($liability_total)) . ' </th></tr>
    <tr><th>Final Total</th><th>' . number_format(round($capital_total + $netprofit + $liability_total)) . '</th></tr>

</table>';
    }

    public function trialbalance()
    {
        $query = DB::raw('SELECT x.*,chartofaccounts.coa_title FROM (SELECT DISTINCT ledgers.account_type,ledgers.transection_type,ledgers.date,ledgers.balance,ledgers.account_id FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id GROUP BY account_id ASC ORDER BY account_type ASC,chartofaccounts.coa_title ASC');
        $trialbalance = DB::select($query);
        return view('reports.trialbalance', compact('trialbalance'));
    }

    public function machinestitch()
    {
        $query = DB::raw('SELECT machine,sum(stiches.stiches) as stiches,stiches.date FROM `stiches` LEFT JOIN employees on stiches.emp_1 = employees.id WHERE MONTH(stiches.date) =' . date('m') . ' GROUP BY machine');
        $machinestitchs = DB::select($query);
        //return $machinestitchs;
        return view('reports/machinestitch', compact('machinestitchs'));
    }

    public function dailymachinstitches()
    {
        $query = DB::raw('SELECT machine,sum(stiches.stiches) as stiches,stiches.date FROM `stiches` LEFT JOIN employees on stiches.emp_1 = employees.id WHERE stiches.date = CURRENT_DATE GROUP BY machine');
        $machinestitchs = DB::select($query);
        return $machinestitchs;
    }

    public function monthlymachinstitches()
    {
        $query = DB::raw('SELECT machine,sum(stiches.stiches) as stiches,stiches.date FROM `stiches` LEFT JOIN employees on stiches.empid = employees.id WHERE MONTH(stiches.date) =' . date('m') . ' GROUP BY machine');
        $machinestitchs = DB::select($query);
        return $machinestitchs;
    }

    public function Employeestitch()
    {

        $query = DB::raw('SELECT stiches.emp_1,employees.name,sum(stiches.stiches) as stiches,stiches.date FROM `stiches` LEFT JOIN employees on stiches.emp_1 = employees.id WHERE  MONTH(stiches.date) =' . date('m') . ' GROUP  BY emp_1');
        $employeestitchs = DB::select($query);
        return view('reports/Employeestitch', compact('employeestitchs'));
    }

    public function dailyemployeestitches()
    {
        $query = DB::raw('SELECT stiches.emp_1,employees.name,sum(stiches.stiches) as stiches,stiches.date FROM `stiches` LEFT JOIN employees on stiches.emp_1 = employees.id WHERE stiches.date = CURRENT_DATE GROUP  BY emp_1');
        $employeestitchs = DB::select($query);
        return $employeestitchs;
    }

    public function monthlyemployeestitches()
    {
        $query = DB::raw('SELECT stiches.emp_1,employees.name,sum(stiches.stiches) as stiches,stiches.date FROM `stiches` LEFT JOIN employees on stiches.emp_1 = employees.id WHERE  MONTH(stiches.date) =' . date('m') . ' GROUP  BY emp_1');
        $employeestitchs = DB::select($query);
        return $employeestitchs;
    }

    public function ledgercoareport()
    {
        $accounts = Ledger::select('ledgers.*', 'chartofaccounts.coa_title')->leftjoin('chartofaccounts', 'chartofaccounts.coa_id', '=', 'ledgers.account_id')->groupby('ledgers.account_id')->get();
        return view('reports/ledgercoareport', compact('accounts'));
    }

    public function coalegreport(Request $request)
    {
        return Ledger::select('ledgers.*', 'chartofaccounts.coa_title')->leftjoin('chartofaccounts', 'chartofaccounts.coa_id', '=', 'ledgers.account_id')->where('account_id', $request->account)->get();

        // foreach(Ledger::select('ledgers.*','chartofaccounts.coa_title')->leftjoin('chartofaccounts','chartofaccounts.coa_id','=','ledgers.account_id')->where('account_id',$request->account)->get() as $account){
        //    echo "<tr>
        //    <td>".$account->coa_title."</td>
        //     <td>".$account->account_type."</td>
        //     <td>".$account->debit_ammount."</td>
        //     <td>".$account->credit_ammount."</td>
        //     <td>".$account->balance."</td>
        //     <td>".$account->date."</td>
        //    </tr>";
        // }
    }
    public function employeereport()
    {
        $employees = Employee::all();
        $query = DB::raw('SELECT SUM(manufacturedetails.inlinetotal) as totalamount,SUM(manufacturedetails.pcs) as totalpcs,manufacturedetails.rate,manufacturedetails.employee,employees.name FROM `manufacturedetails` LEFT JOIN employees on employees.id =manufacturedetails.employee WHERE manufacturedetails.paid = false GROUP BY `manufacturedetails`.`employee` DESC');
        $employee = DB::select($query);
        $chartofaccounts = DB::table('cprelations')->select('cprelations.*', 'chartofaccounts.account_type')->leftjoin('chartofaccounts', 'chartofaccounts.coa_id', 'cprelations.acc_id')->orderByRaw("FIELD(def ,1) DESC")->get();

        //return $employee;
        return view('reports/employee', compact('employee', 'chartofaccounts', 'employees'));
    }
    public function manufacturePay(Request $request){
        
        $update = Manufacturedetail::where('id',$request->manufacture_id)->update(['paid'=>true]);
        if($update){
            echo 'done';
        }
    }

    public function manufacturedetail(Request $request)
    {
        Carbon::setWeekStartsAt(Carbon::SATURDAY);
        Carbon::setWeekEndsAt(Carbon::THURSDAY);
        $employeebalance = 0;
        $query3 = DB::raw('SELECT x.balance FROM (SELECT DISTINCT ledgers.balance,ledgers.account_id,ledgers.account_type,ledgers.date FROM ledgers ORDER BY date DESC) x LEFT JOIN chartofaccounts on chartofaccounts.coa_id=x.account_id where x.account_id = "liability_employee_' . $request->employee . '" && yearweek(x.date) = yearweek(curdate())  GROUP BY account_id');
        foreach (DB::select($query3) as $empbalance) {
            $employeebalance = $empbalance->balance;
        }
        if ($request->toDate != "" && $request->fromDate != "") {
            $manufacturedetails = Manufacture::select('manufactures.date', 'manufacturedetails.*', 'employees.name')->leftjoin('manufacturedetails', 'manufactures.id', '=', 'manufacturedetails.manufacture_id')->leftjoin('employees', 'employees.id', '=', 'manufacturedetails.employee')->where('manufacturedetails.employee', $request->employee)->whereDate('manufactures.date', '>=', $request->fromDate)->whereDate('manufactures.date', '<=', $request->toDate)->whereDate('manufacturedetails.paid', '=', 0)->get();
        } else {
            $manufacturedetails = Manufacture::select('manufactures.date', 'manufacturedetails.*', 'employees.name')->leftjoin('manufacturedetails', 'manufactures.id', '=', 'manufacturedetails.manufacture_id')->leftjoin('employees', 'employees.id', '=', 'manufacturedetails.employee')->where('manufacturedetails.employee', $request->employee)->whereDate('manufactures.date', '>=', Carbon::now()->startOfWeek())->whereDate('manufactures.date', '<=', Carbon::now()->endOfWeek())->whereDate('manufacturedetails.paid', '=', 0)->get();
        }
        echo json_encode(array($employeebalance, $manufacturedetails));
    }
    public function employeepay(Request $request)
    {

        if ($request->cash_available < $request->totalamount) {
            $request->session()->flash('message.level', 'danger');
            $request->session()->flash('message.content', 'Amount To Be Paid Must Be Less Then Available Amount');
        } else {
            if (isset($request->pay)) {
                $emppayment = new employeepayments;
                $emppayment->totalamount = $request->totalamount;
                $emppayment->coa = $request->coa;
                if ($emppayment->save()) {
                    $emppid = employeepayments::orderby('id', 'desc')->first();
                    for ($i = 0; $i < count($request->pay); $i++) {
                        $emppaymentdetails = new employeepaymentsdetail;
                        $emppaymentdetails->emppid = $emppid->id;
                        $emppaymentdetails->nogots = $request->pcs[$i];
                        $emppaymentdetails->amount = $request->employeeamount[$i];
                        $emppaymentdetails->employee = $request->employee[$i];
                        if ($emppaymentdetails->save()) {
                            Manufacturedetail::where('employee', $request->employee[$i])->update(['paid' => true]);
                            $this->liabilityledger('liability_employee_' . $request->employee[$i], 5, "liabilities", $request->employeeamount[$i], null, 55, 0001, date('Y-m-d'));
                        }
                    }
                    $this->assetledger($request->coa, 1, "assets", null, $request->totalamount, 55, 0001, date('Y-m-d'));
                }
            }
            $request->session()->flash('message.level', 'success');
            $request->session()->flash('message.content', 'Amounts Paid');
        }
        return redirect('employeereport');
    }

    public function employeeDebitDetails(Request $request)
    {
        Carbon::setWeekStartsAt(Carbon::SATURDAY);
        Carbon::setWeekEndsAt(Carbon::THURSDAY);
        if ($request->toDate != "" && $request->fromDate != "") {
            $payrolls = Payrolladvance::where('employee_id', $request->employee)->whereDate('created_at', '>=', $request->fromDate)->whereDate('created_at', '<', $request->toDate)->orderby('created_at', 'desc')->get();
            $bonuses = Bonus::where('emp', $request->employee)->whereDate('created_at', '>=', $request->fromDate)->whereDate('created_at', '<', $request->toDate)->orderby('created_at', 'desc')->get();
            $creditAmount = Ledger::select('credit_ammount', 'date')->where('account_id', 'liability_employee_' . $request->employee)->whereDate('date', '>=', $request->fromDate)->whereDate('date', '<', $request->toDate)->orderby('date', 'desc')->get();
            $debitAmount = Ledger::select('debit_ammount', 'date')->where('account_id', 'liability_employee_' . $request->employee)->whereDate('date', '>=', $request->fromDate)->whereDate('date', '<', $request->toDate)->orderby('date', 'desc')->get();
        } else {
            $payrolls = Payrolladvance::where('employee_id', $request->employee)->whereDate('created_at', '>=', Carbon::now()->startOfWeek())->whereDate('created_at', '<=', Carbon::now()->endOfWeek())->orderby('created_at', 'desc')->get();

            $bonuses = Bonus::where('emp', $request->employee)->whereDate('created_at', '>=', Carbon::now()->startOfWeek())->whereDate('created_at', '<=', Carbon::now()->endOfWeek())->get();

            $creditAmount = Ledger::select('credit_ammount', 'date')->where('account_id', 'liability_employee_' . $request->employee)->whereDate('date', '>=', Carbon::now()->startOfWeek())->whereDate('date', '<=', Carbon::now()->endOfWeek())->orderby('date', 'desc')->get();

           $debitAmount = Ledger::select('debit_ammount', 'date')->where('account_id', 'liability_employee_' . $request->employee)->whereDate('date', '>=', Carbon::now()->startOfWeek())->whereDate('date', '<=', Carbon::now()->endOfWeek())->orderby('date', 'desc')->get();
     }

        echo json_encode(array($payrolls, $bonuses, $creditAmount,$debitAmount));
    }

    public function creditSaleReport(){
       $creditsales = Sale::select(DB::raw("SUM(subtotal) as amount"),'sales.customer','customers.customer_name')->leftjoin('customers','sales.customer','=','customers.customer_id')->groupby('sales.customer')->where('status',0)->get();
       //return $creditsales; 
       return view('reports/creditsale',compact('creditsales'));
    }

    public function customerCreditSales($customer){
        $customername = Customer::where('customer_id',$customer)->first()->customer_name;
        $creditsales = Sale::select('sales.*','customers.customer_name')->leftjoin('customers','sales.customer','=','customers.customer_id')->where('sales.status',0)->where('sales.customer',$customer)->get();
        //return $creditsales; 
        return view('reports/allcreditsales',compact('creditsales','customername'));

    }
}
