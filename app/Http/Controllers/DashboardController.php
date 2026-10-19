<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Invoice;
use App\Models\AiLog;
use App\Enums\ProjectStatus;
use App\Enums\InvoiceStatus;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Revenue statistics
        $totalRevenue = Invoice::where('status', InvoiceStatus::Paid)->sum('amount');
        
        // Project statistics
        $activeProjectsCount = Project::where('status', ProjectStatus::Active)->count();
        
        // Outstanding invoices
        $unpaidInvoicesCount = Invoice::where('status', InvoiceStatus::Unpaid)->count();
        $unpaidInvoicesSum = Invoice::where('status', InvoiceStatus::Unpaid)->sum('amount');

        // AI statistics
        $aiOperationsCount = AiLog::where('status', 'success')->count();

        // Recent projects with calculated estimated progress fallback
        $recentProjects = Project::with(['client', 'assignedMember'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($project) {
                // Fallback progress estimate based on ProjectStatus enum
                $project->estimated_progress = match ($project->status) {
                    ProjectStatus::Pending => 10,
                    ProjectStatus::Active => 55,
                    ProjectStatus::Completed => 100,
                    default => 0,
                };
                return $project;
            });

        return view('dashboard', compact(
            'totalRevenue',
            'activeProjectsCount',
            'unpaidInvoicesCount',
            'unpaidInvoicesSum',
            'aiOperationsCount',
            'recentProjects'
        ));
    }
}