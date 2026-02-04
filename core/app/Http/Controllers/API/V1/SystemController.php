<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Models\Slider;
use App\Models\Setting;
use App\Models\MaintenanceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SystemController extends Controller
{
    use ApiResponse;

    /**
     * Get banners/sliders.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function banners(Request $request)
    {
        try {
            $languageId = $request->input('language_id', 1);
            
            $sliders = Slider::where('status', 1)
                ->where('language_id', $languageId)
                ->get()
                ->map(function ($slider) {
                    return [
                        'id' => $slider->id,
                        'name' => $slider->name,
                        'description' => $slider->desc,
                        'image' => $slider->image ? url('assets/front/banner/' . $slider->image) : null,
                        'offer' => $slider->offer,
                    ];
                });

            return $this->successResponse([
                'banners' => $sliders
            ], 'Banners retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get banners failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve banners.');
        }
    }

    /**
     * Check maintenance status.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function maintenanceStatus()
    {
        try {
            $maintenance = MaintenanceSetting::first();
            
            $isUnderMaintenance = false;
            $message = null;
            
            if ($maintenance && $maintenance->status == 1) {
                $isUnderMaintenance = true;
                $message = $maintenance->message ?? 'The app is currently under maintenance. Please try again later.';
            }

            return $this->successResponse([
                'under_maintenance' => $isUnderMaintenance,
                'message' => $message,
            ], 'Maintenance status retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get maintenance status failed: ' . $e->getMessage());
            return $this->successResponse([
                'under_maintenance' => false,
                'message' => null,
            ], 'Maintenance status retrieved successfully');
        }
    }

    /**
     * Check app version and update requirements.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function appVersion(Request $request)
    {
        try {
            $currentVersion = $request->input('version', '1.0.0');
            $platform = $request->input('platform', 'android'); // android or ios
            
            $settings = Setting::first();
            
            // Default version info
            $latestVersion = '1.0.0';
            $minVersion = '1.0.0';
            $updateUrl = null;
            
            if ($settings) {
                if ($platform === 'ios') {
                    $latestVersion = $settings->ios_version ?? '1.0.0';
                    $minVersion = $settings->ios_min_version ?? '1.0.0';
                    $updateUrl = $settings->ios_store_url ?? null;
                } else {
                    $latestVersion = $settings->android_version ?? '1.0.0';
                    $minVersion = $settings->android_min_version ?? '1.0.0';
                    $updateUrl = $settings->android_store_url ?? null;
                }
            }
            
            $forceUpdate = version_compare($currentVersion, $minVersion, '<');
            $updateAvailable = version_compare($currentVersion, $latestVersion, '<');

            return $this->successResponse([
                'current_version' => $currentVersion,
                'latest_version' => $latestVersion,
                'min_version' => $minVersion,
                'update_available' => $updateAvailable,
                'force_update' => $forceUpdate,
                'update_url' => $updateUrl,
            ], 'App version info retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get app version failed: ' . $e->getMessage());
            return $this->successResponse([
                'current_version' => $request->input('version', '1.0.0'),
                'latest_version' => '1.0.0',
                'update_available' => false,
                'force_update' => false,
            ], 'App version info retrieved successfully');
        }
    }

    /**
     * Get app settings and configuration.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function settings()
    {
        try {
            $settings = Setting::first();
            
            $config = [
                'app_name' => $settings->title ?? 'Telco ISP',
                'support_phone' => $settings->phone ?? null,
                'support_email' => $settings->email ?? null,
                'address' => $settings->address ?? null,
                'logo' => $settings->logo ? url('assets/front/img/' . $settings->logo) : null,
                'facebook' => $settings->facebook ?? null,
                'twitter' => $settings->twitter ?? null,
                'instagram' => $settings->instagram ?? null,
            ];

            return $this->successResponse([
                'settings' => $config
            ], 'Settings retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Get settings failed: ' . $e->getMessage());
            return $this->serverErrorResponse('Failed to retrieve settings.');
        }
    }
}
