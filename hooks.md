# FILTERS
## GENERIC
- - apply_filters('sim-template-filter', $templateFile);
apply_filters('sim_role_description', '', $role);

## Admin module
- apply_filters('sim_submenu_description', '', $moduleSlug, $moduleName);
- apply_filters('sim_submenu_options', '', $moduleSlug, $settings, $moduleName);
- apply_filters('sim_email_settings', '', $moduleSlug, $settings, $moduleName);
- apply_filters('sim_module_data', '', $moduleSlug, $settings, $moduleName);
- apply_filters('sim_module_functions', '', $moduleSlug, $settings, $moduleName);
- apply_filters('sim_module_updated', $options, $moduleSlug, $Modules[$moduleSlug]);
- apply_filters('sim_module_updated', $options, $slug, $Modules[$slug]);

# Actions
## Generic
- do_action('sim_roles_changed', $user, $newRoles);
- do_action( 'sim_approved_user', $userId);
- do_action('sim_plugin_update', $oldVersion);
- do_action('sim_module_deactivated', $moduleSlug, $options);
- do_action('sim_module_activated', $slug, $options);

## Admin
- do_action('sim_module_actions');
- do_action('sim-admin-settings-post');