<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Models\Package;
use App\Models\Packageorder;
use App\Models\MbtBindUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PackageController extends Controller
{
    use ApiResponse;

    /**
     * Get all available packages.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $languageId = $request->input('language_id', 1);
            
            $packages = Package::where('status', 1)
                ->where('language_id', $languageId)
                ->orderBy('price', 'asc')
                ->get()
                ->map(function ($package) {
                    return $this->formatPackage($package);
                });

            return $this->successResponse([
                'packages' => $packages,
                'count' => $packages->count()
            ], 'Packages retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get packages failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve packages.');
        }
    }

    /**
     * Get package details.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $package = Package::find($id);
            
            if (!$package) {
                return $this->notFoundResponse('Package not found.');
            }

            return $this->successResponse([
                'package' => $this->formatPackageDetailed($package)
            ], 'Package details retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get package details failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve package details.');
        }
    }

    /**
     * Get user's current and past packages.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function myPackages(Request $request)
    {
        try {
            $user = $request->user();
            
            // Get package orders for user
            $packageOrders = Packageorder::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($order) {
                    $package = Package::find($order->package_id);
                    return [
                        'order_id' => $order->id,
                        'package_id' => $order->package_id,
                        'package_name' => $package ? $package->name : 'Unknown',
                        'package_cost' => $order->package_cost,
                        'payment_status' => $order->payment_status,
                        'status' => $order->status,
                        'invoice_number' => $order->invoice_number,
                        'method' => $order->method,
                        'ordered_at' => $order->created_at ? $order->created_at->toISOString() : null,
                    ];
                });
            
            // Get current package from MBT bind user if available
            $currentPackage = null;
            if ($user->bind_user_id) {
                $mbtUser = MbtBindUser::find($user->bind_user_id);
                if ($mbtUser) {
                    $currentPackage = [
                        'name' => $mbtUser->Now_package,
                        'monthly_cost' => $mbtUser->Monthly_Cost,
                        'bandwidth' => $mbtUser->Bandwidth,
                        'expire_time' => $mbtUser->user_expire_time,
                        'start_time' => $mbtUser->user_start_time,
                    ];
                }
            }

            return $this->successResponse([
                'current_package' => $currentPackage,
                'package_orders' => $packageOrders,
                'orders_count' => $packageOrders->count()
            ], 'My packages retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get my packages failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve your packages.');
        }
    }

    /**
     * Format package data for API response.
     */
    protected function formatPackage($package): array
    {
        return [
            'id' => $package->id,
            'name' => $package->name,
            'price' => $package->price,
            'discount_price' => $package->discount_price,
            'speed' => $package->speed,
            'time' => $package->time,
            'status' => $package->status,
        ];
    }

    /**
     * Format detailed package data for API response.
     */
    protected function formatPackageDetailed($package): array
    {
        return array_merge($this->formatPackage($package), [
            'feature' => $package->feature,
            'language_id' => $package->language_id,
            'created_at' => $package->created_at ? $package->created_at->toISOString() : null,
        ]);
    }
}
