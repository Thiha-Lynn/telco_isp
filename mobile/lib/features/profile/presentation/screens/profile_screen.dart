import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:animate_do/animate_do.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../auth/presentation/bloc/auth_bloc.dart';
import '../../data/models/bind_user_model.dart';
import '../bloc/profile_bloc.dart';
import '../widgets/profile_info_card.dart';
import '../widgets/profile_header.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  late ProfileBloc _profileBloc;

  @override
  void initState() {
    super.initState();
    _profileBloc = ProfileBloc();
    _profileBloc.add(ProfileLoadRequested());
  }

  @override
  void dispose() {
    _profileBloc.close();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return BlocProvider.value(
      value: _profileBloc,
      child: BlocBuilder<AuthBloc, AuthState>(
        builder: (context, authState) {
          final user = authState is AuthAuthenticated ? authState.user : null;

          return BlocBuilder<ProfileBloc, ProfileState>(
            builder: (context, profileState) {
              final bindUser = profileState is ProfileLoaded 
                  ? profileState.primaryBindUser 
                  : null;
              final isLoading = profileState is ProfileLoading;

              return RefreshIndicator(
                onRefresh: () async {
                  _profileBloc.add(ProfileRefreshRequested());
                  // Wait for state change
                  await _profileBloc.stream.firstWhere(
                    (state) => state is ProfileLoaded || state is ProfileError,
                  );
                },
                child: SingleChildScrollView(
                  physics: const AlwaysScrollableScrollPhysics(
                    parent: BouncingScrollPhysics(),
                  ),
                  child: Column(
                    children: [
                      // Profile Header Card
                      FadeInDown(
                        duration: const Duration(milliseconds: 500),
                        child: ProfileHeader(user: user, bindUser: bindUser),
                      ),

                      const SizedBox(height: 24),

                      // Account Information Card
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        child: FadeInUp(
                          duration: const Duration(milliseconds: 500),
                          delay: const Duration(milliseconds: 100),
                          child: ProfileInfoCard(
                            title: 'Account Information',
                            icon: Icons.person_outline_rounded,
                            items: [
                              ProfileInfoItem(
                                label: 'Register Phone',
                                value: user?.phone ?? '-',
                                icon: Icons.phone_outlined,
                              ),
                              ProfileInfoItem(
                                label: 'Register Date',
                                value: _formatDate(user?.createdAt),
                                icon: Icons.calendar_today_outlined,
                              ),
                              ProfileInfoItem(
                                label: 'Name',
                                value: user?.name ?? '-',
                                icon: Icons.badge_outlined,
                              ),
                              ProfileInfoItem(
                                label: 'Email',
                                value: user?.email ?? '-',
                                icon: Icons.email_outlined,
                              ),
                              ProfileInfoItem(
                                label: 'Account Status',
                                value: user?.isActive == true ? 'Normal' : 'Inactive',
                                icon: Icons.verified_outlined,
                                valueColor: user?.isActive == true 
                                    ? AppColors.success 
                                    : AppColors.error,
                              ),
                            ],
                          ),
                        ),
                      ),

                      const SizedBox(height: 16),

                      // Broadband Information Card
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        child: FadeInUp(
                          duration: const Duration(milliseconds: 500),
                          delay: const Duration(milliseconds: 200),
                          child: _buildBroadbandCard(bindUser, isLoading),
                        ),
                      ),

                      const SizedBox(height: 16),

                      // Package Information Card
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        child: FadeInUp(
                          duration: const Duration(milliseconds: 500),
                          delay: const Duration(milliseconds: 300),
                          child: _buildPackageCard(bindUser, isLoading),
                        ),
                      ),

                      const SizedBox(height: 32),
                    ],
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }

  Widget _buildBroadbandCard(dynamic bindUser, bool isLoading) {
    if (isLoading) {
      return ProfileInfoCard(
        title: 'Broadband Information',
        icon: Icons.wifi_rounded,
        isLoading: true,
        items: const [],
      );
    }

    final hasBroadband = bindUser != null;

    return ProfileInfoCard(
      title: 'Broadband Information',
      icon: Icons.wifi_rounded,
      items: [
        ProfileInfoItem(
          label: 'MBT ID',
          value: hasBroadband ? (bindUser.userName ?? 'N/A') : 'Not Bound',
          icon: Icons.tag_rounded,
          valueColor: hasBroadband ? null : AppColors.textLight,
        ),
        ProfileInfoItem(
          label: 'Broadband Name',
          value: hasBroadband ? (bindUser.realName ?? '-') : '-',
          icon: Icons.person_pin_outlined,
        ),
        ProfileInfoItem(
          label: 'Broadband Phone',
          value: hasBroadband ? (bindUser.phone ?? '-') : '-',
          icon: Icons.phone_callback_outlined,
        ),
        ProfileInfoItem(
          label: 'Install Address',
          value: hasBroadband ? (bindUser.address ?? '-') : '-',
          icon: Icons.location_on_outlined,
        ),
        ProfileInfoItem(
          label: 'Service Type',
          value: hasBroadband ? (bindUser.serviceType ?? '-') : '-',
          icon: Icons.router_outlined,
        ),
        ProfileInfoItem(
          label: 'Bandwidth',
          value: hasBroadband ? (bindUser.bandwidth ?? '-') : '-',
          icon: Icons.speed_outlined,
        ),
        ProfileInfoItem(
          label: 'Status',
          value: hasBroadband ? bindUser.statusText : '-',
          icon: Icons.circle_outlined,
          valueColor: hasBroadband && bindUser.isActive 
              ? AppColors.success 
              : AppColors.error,
        ),
      ],
    );
  }

  Widget _buildPackageCard(BindUser? bindUser, bool isLoading) {
    if (isLoading) {
      return ProfileInfoCard(
        title: 'Package Information',
        icon: Icons.inventory_2_outlined,
        isLoading: true,
        items: const [],
      );
    }

    final hasBroadband = bindUser != null;
    final monthlyCost = hasBroadband && bindUser.monthlyCost != null
        ? _formatCurrency(bindUser.monthlyCost)
        : '-';
    final balance = hasBroadband && bindUser.balance != null
        ? _formatCurrency(bindUser.balance)
        : '-';

    return ProfileInfoCard(
      title: 'Package Information',
      icon: Icons.inventory_2_outlined,
      items: [
        ProfileInfoItem(
          label: 'Package',
          value: hasBroadband ? (bindUser.package ?? '-') : '-',
          icon: Icons.signal_wifi_4_bar_rounded,
        ),
        ProfileInfoItem(
          label: 'Monthly Cost',
          value: monthlyCost,
          icon: Icons.payments_outlined,
        ),
        ProfileInfoItem(
          label: 'Balance',
          value: balance,
          icon: Icons.account_balance_wallet_outlined,
          valueColor: hasBroadband && double.tryParse(bindUser.balance ?? '0') != null
              && double.parse(bindUser.balance!) > 0
              ? AppColors.success
              : null,
        ),
        ProfileInfoItem(
          label: 'Expiry Date',
          value: hasBroadband ? _formatExpiryDate(bindUser.expireTime) : '-',
          icon: Icons.event_outlined,
          valueColor: _getExpiryColor(bindUser?.expireTime),
        ),
      ],
    );
  }

  String _formatDate(DateTime? date) {
    if (date == null) return '-';
    return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}, ${date.hour.toString().padLeft(2, '0')}:${date.minute.toString().padLeft(2, '0')} ${date.hour >= 12 ? 'PM' : 'AM'}';
  }

  String _formatCurrency(String? amount) {
    if (amount == null) return '-';
    final numAmount = double.tryParse(amount);
    if (numAmount == null) return amount;
    
    final formatted = numAmount.toStringAsFixed(0).replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (Match m) => '${m[1]},',
    );
    return '$formatted MMK';
  }

  String _formatExpiryDate(String? dateStr) {
    if (dateStr == null) return '-';
    try {
      final date = DateTime.parse(dateStr);
      final months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      return '${months[date.month - 1]} ${date.day}, ${date.year}';
    } catch (_) {
      return dateStr;
    }
  }

  Color? _getExpiryColor(String? expireTime) {
    if (expireTime == null) return null;
    
    try {
      final expiry = DateTime.parse(expireTime);
      final now = DateTime.now();
      final daysUntilExpiry = expiry.difference(now).inDays;
      
      if (daysUntilExpiry < 0) {
        return AppColors.error;
      } else if (daysUntilExpiry <= 7) {
        return AppColors.warning;
      } else {
        return AppColors.success;
      }
    } catch (_) {
      return null;
    }
  }
}
