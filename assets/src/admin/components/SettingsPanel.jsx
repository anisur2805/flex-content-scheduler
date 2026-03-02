import { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Plugin settings panel component.
 *
 * @since 1.0.0
 *
 * @return {JSX.Element} Settings panel UI.
 */
export default function SettingsPanel() {
  const current = window.flexCSAdmin?.settings || {};
  const [settings, setSettings] = useState({
    default_action: current.default_action || 'unpublish',
    cron_enabled: current.cron_enabled ?? true,
    notification_email: current.notification_email || '',
    allowed_redirect_hosts: Array.isArray(current.allowed_redirect_hosts) ? current.allowed_redirect_hosts.join(', ') : ''
  });
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');

  const saveSettings = async () => {
    setSaving(true);
    setMessage('');
    try {
      const updated = await apiFetch({
        path: '/flex-cs/v1/settings',
        method: 'PUT',
        data: {
          ...settings,
          allowed_redirect_hosts: settings.allowed_redirect_hosts
            .split(',')
            .map((host) => host.trim())
            .filter(Boolean)
        },
        headers: { 'X-WP-Nonce': window.flexCSAdmin?.nonce }
      });
      setSettings({
        ...updated,
        allowed_redirect_hosts: Array.isArray(updated.allowed_redirect_hosts) ? updated.allowed_redirect_hosts.join(', ') : ''
      });
      setMessage(__('Settings saved.', 'flex-content-scheduler'));
    } catch {
      setMessage(__('Failed saving settings.', 'flex-content-scheduler'));
    } finally {
      setSaving(false);
    }
  };

  return (
    <section>
      <h2>{__('Settings', 'flex-content-scheduler')}</h2>
      <div className="flex-cs-settings">
        <label>
          {__('Default action', 'flex-content-scheduler')}
          <select
            value={settings.default_action}
            onChange={(e) => setSettings({ ...settings, default_action: e.target.value })}
          >
            <option value="unpublish">{__('Unpublish', 'flex-content-scheduler')}</option>
            <option value="delete">{__('Delete', 'flex-content-scheduler')}</option>
            <option value="redirect">{__('Redirect', 'flex-content-scheduler')}</option>
            <option value="change_status">{__('Change status', 'flex-content-scheduler')}</option>
            <option value="sticky">{__('Sticky', 'flex-content-scheduler')}</option>
            <option value="unsticky">{__('Unsticky', 'flex-content-scheduler')}</option>
          </select>
        </label>

        <label>
          <input
            type="checkbox"
            checked={settings.cron_enabled}
            onChange={(e) => setSettings({ ...settings, cron_enabled: e.target.checked })}
          />
          {__('Enable cron processing', 'flex-content-scheduler')}
        </label>

        <label>
          {__('Notification email', 'flex-content-scheduler')}
          <input
            type="email"
            value={settings.notification_email}
            onChange={(e) => setSettings({ ...settings, notification_email: e.target.value })}
          />
        </label>

        <label>
          {__('Allowed redirect hosts (comma-separated)', 'flex-content-scheduler')}
          <input
            type="text"
            value={settings.allowed_redirect_hosts}
            onChange={(e) => setSettings({ ...settings, allowed_redirect_hosts: e.target.value })}
            placeholder={__('example.com, another-site.com', 'flex-content-scheduler')}
          />
        </label>
      </div>
      <p>
        <button type="button" className="button button-primary" disabled={saving} onClick={saveSettings}>
          {saving ? __('Saving...', 'flex-content-scheduler') : __('Save Settings', 'flex-content-scheduler')}
        </button>
      </p>
      {message ? <p>{message}</p> : null}
    </section>
  );
}
