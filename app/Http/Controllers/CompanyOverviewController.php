<?php

namespace App\Http\Controllers;

use App\Actions\Company\GetCompanyInfoAction;
use App\Actions\Company\GetCompanyOverviewDataAction;
use App\Actions\Company\GetHierarchyDataAction;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CompanyOverviewController extends Controller
{
   public function index(GetCompanyInfoAction $infoAction, GetCompanyOverviewDataAction $overviewAction, GetHierarchyDataAction $hierarchyAction
    ) {

        $companyInfo = $infoAction->execute();

        $overviewData = $overviewAction->execute();

        $hierarchyData = $hierarchyAction->execute();

        return Inertia::render('CompanyOverview/Index', array_merge(
            ['companyInfo' => $companyInfo],
            $overviewData,
            $hierarchyData
        ));
    }

}