// side_panel.dart
import 'package:flutter/material.dart';
import 'colors.dart'; 
import 'services/auth_service.dart';

class SideMenuItem {
  final IconData icon;
  final String label;
  final String route;
  final bool selected;
  
  const SideMenuItem({
    required this.icon,
    required this.label,
    this.route = '/',
    this.selected = false,
  });
}

class SidePanel extends StatelessWidget {
  final List<SideMenuItem> items;
  final String currentRoute;
  final Function(String) onItemTap;

  const SidePanel({
    super.key,
    required this.items,
    required this.currentRoute,
    required this.onItemTap,
  });

  @override
  Widget build(BuildContext context) {
    return Drawer(
      backgroundColor: Colors.white,
      child: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 12, 16),
              child: Row(
                children: [
                  const CircleAvatar(
                    radius: 16,
                    backgroundColor: Color(0xFFE3F2FD),
                    child: Icon(
                      Icons.local_hospital,
                      color: AppColors.primary,
                      size: 18,
                    ),
                  ),
                  const SizedBox(width: 8),
                  const Text(
                    'Polyclinic',
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                    ),
                  ),
                  const Spacer(),
                  IconButton(
                    icon: const Icon(Icons.close, color: AppColors.textGrey),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            Expanded(
              child: ListView.builder(
                padding: const EdgeInsets.symmetric(vertical: 8),
                itemCount: items.length,
                itemBuilder: (context, index) {
                  final SideMenuItem item = items[index];
                  final isSelected = currentRoute == item.route;
                  
                  return Container(
                    margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 3),
                    decoration: BoxDecoration(
                      color: isSelected ? AppColors.primary : Colors.transparent,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: ListTile(
                      dense: true,
                      leading: Icon(
                        item.icon,
                        size: 20,
                        color: isSelected ? Colors.white : AppColors.textGrey,
                      ),
                      title: Text(
                        item.label,
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: isSelected ? FontWeight.w600 : FontWeight.w400,
                          color: isSelected ? Colors.white : AppColors.textDark,
                        ),
                      ),
                      onTap: () {
                        Navigator.pop(context);
                        if (item.route != currentRoute) {
                          onItemTap(item.route);
                        }
                      },
                    ),
                  );
                },
              ),
            ),
            const Divider(height: 1),

ListTile(
  leading: const Icon(
    Icons.logout,
    color: Colors.red,
  ),
  title: const Text(
    'Logout',
    style: TextStyle(
      color: Colors.red,
      fontWeight: FontWeight.w600,
    ),
  ),
  onTap: () async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Logout'),
        content: const Text(
          'Are you sure you want to logout?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Logout'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      await AuthService.logout();

      if (context.mounted) {
        Navigator.pushNamedAndRemoveUntil(
          context,
          '/login',
          (route) => false,
        );
      }
    }
  },
),

const Divider(height: 1),

const Padding(
  padding: EdgeInsets.symmetric(vertical: 12),
  child: Text(
    'Polyclinic Admin v2.0',
    style: TextStyle(
      color: AppColors.textGrey,
      fontSize: 11,
    ),
  ),
),
          ],
        ),
      ),
    );
  }
}