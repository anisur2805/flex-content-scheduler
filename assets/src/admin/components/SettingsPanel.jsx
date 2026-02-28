import { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';

export default function SettingsPanel() {
  const current = window.fcsAdmin?.settings || {};
  const [settings, setSettings] = useState({
    default_action: current.default_action || 'unpublish',
    cron_enabled: current.cron_enabled ?? true,
    notification_email: current.notification_email || ''
  });
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');

  const saveSettings = async () => {
    setSaving(true);
    setMessage('');
    try {
      const updated = await apiFetch({
        path: '/fcs/v1/settings',
        method: 'PUT',
        data: settings,
        headers: { 'X-WP-Nonce': window.fcsAdmin?.nonce }
      });
      setSettings(updated);
      setMessage('Settings saved.');
    } catch {
      setMessage('Failed saving settings.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <section>
      <h2>Settings</h2>
      <div className="fcs-settings">
        <label>
          Default action
          <select
            value={settings.default_action}
            onChange={(e) => setSettings({ ...settings, default_action: e.target.value })}
          >
            <option value="unpublish">Unpublish</option>
            <option value="delete">Delete</option>
            <option value="redirect">Redirect</option>
            <option value="change_status">Change status</option>
          </select>
        </label>

        <label>
          <input
            type="checkbox"
            checked={settings.cron_enabled}
            onChange={(e) => setSettings({ ...settings, cron_enabled: e.target.checked })}
          />
          Enable cron processing
        </label>

        <label>
          Notification email
          <input
            type="email"
            value={settings.notification_email}
            onChange={(e) => setSettings({ ...settings, notification_email: e.target.value })}
          />
        </label>
      </div>
      <p>
        <button type="button" className="button button-primary" disabled={saving} onClick={saveSettings}>
          {saving ? 'Saving...' : 'Save Settings'}
        </button>
      </p>
      {message ? <p>{message}</p> : null}
    </section>
  );
}
