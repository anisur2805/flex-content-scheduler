import { useEffect, useMemo, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/** @type {{ expiry_date: string, expiry_action: string, redirect_url: string, new_status: string }} */
const initialState = {
  expiry_date: '',
  expiry_action: 'unpublish',
  redirect_url: '',
  new_status: 'draft'
};

/**
 * Convert a UTC date string to a local datetime-local input value.
 *
 * @since 1.0.0
 *
 * @param {string} utcDate UTC date string (Y-m-d H:i:s).
 * @return {string} Local datetime string for input[type=datetime-local].
 */
function toLocalInput(utcDate) {
  if (!utcDate) return '';
  const d = new Date(utcDate.replace(' ', 'T') + 'Z');
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

/**
 * Convert a local datetime-local input value to a UTC datetime string.
 *
 * @since 1.0.0
 *
 * @param {string} localValue Local datetime string from input.
 * @return {string} UTC datetime string (Y-m-d H:i:s).
 */
function toUtcString(localValue) {
  if (!localValue) return '';
  const d = new Date(localValue);
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getUTCFullYear()}-${pad(d.getUTCMonth() + 1)}-${pad(d.getUTCDate())} ${pad(d.getUTCHours())}:${pad(d.getUTCMinutes())}:00`;
}

/**
 * MetaBox component for managing content expiry schedules on post edit screens.
 *
 * @since 1.0.0
 *
 * @return {JSX.Element} MetaBox UI.
 */
export default function MetaBox() {
  const [schedule, setSchedule] = useState(initialState);
  const [existing, setExisting] = useState(null);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState(null);

  const postId = window.flexCSMetabox?.postId || 0;

  useEffect(() => {
    if (!postId) return;
    setLoading(true);
    apiFetch({
      path: `/flex-cs/v1/schedules/post/${postId}`,
      headers: { 'X-WP-Nonce': window.flexCSMetabox?.nonce || '' }
    })
      .then((res) => {
        if (res?.id) {
          setExisting(res);
          setSchedule({
            expiry_date: toLocalInput(res.expiry_date),
            expiry_action: res.expiry_action || 'unpublish',
            redirect_url: res.redirect_url || '',
            new_status: res.new_status || 'draft'
          });
        }
      })
      .catch((error) => {
        const text = error?.message ? `Failed loading schedule: ${error.message}` : 'Failed loading schedule.';
        setMessage({ type: 'error', text });
      })
      .finally(() => setLoading(false));
  }, [postId]);

  const countdown = useMemo(() => {
    if (!existing?.expiry_date) return null;
    const delta = new Date(existing.expiry_date.replace(' ', 'T') + 'Z').getTime() - Date.now();
    if (delta <= 0) return __('Expired', 'flex-content-scheduler');
    const days = Math.floor(delta / (1000 * 60 * 60 * 24));
    const hours = Math.floor((delta % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    /* translators: %1$d: number of days, %2$d: number of hours */
    return `${__('Expires in', 'flex-content-scheduler')} ${days} ${__('days', 'flex-content-scheduler')}, ${hours} ${__('hours', 'flex-content-scheduler')}`;
  }, [existing]);

  const submit = async () => {
    setSaving(true);
    setMessage(null);

    const payload = {
      post_id: postId,
      expiry_date: toUtcString(schedule.expiry_date),
      expiry_action: schedule.expiry_action,
      redirect_url: schedule.redirect_url,
      new_status: schedule.new_status
    };

    try {
      const method = existing ? 'PUT' : 'POST';
      const path = existing ? `/flex-cs/v1/schedules/${existing.id}` : '/flex-cs/v1/schedules';
      const response = await apiFetch({
        path,
        method,
        data: payload,
        headers: { 'X-WP-Nonce': window.flexCSMetabox?.nonce || '' }
      });
      setExisting(response);
      setMessage({ type: 'success', text: __('Schedule saved.', 'flex-content-scheduler') });
    } catch (error) {
      const text = error?.message ? `${__('Failed to save schedule:', 'flex-content-scheduler')} ${error.message}` : __('Failed to save schedule.', 'flex-content-scheduler');
      setMessage({ type: 'error', text });
    } finally {
      setSaving(false);
    }
  };

  const remove = async () => {
    if (!existing?.id) return;
    setSaving(true);
    try {
      await apiFetch({
        path: `/flex-cs/v1/schedules/${existing.id}`,
        method: 'DELETE',
        headers: { 'X-WP-Nonce': window.flexCSMetabox?.nonce || '' }
      });
      setExisting(null);
      setSchedule(initialState);
      setMessage({ type: 'success', text: __('Schedule deleted.', 'flex-content-scheduler') });
    } catch (error) {
      const text = error?.message ? `${__('Failed deleting schedule:', 'flex-content-scheduler')} ${error.message}` : __('Failed deleting schedule.', 'flex-content-scheduler');
      setMessage({ type: 'error', text });
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="flex-cs-metabox">
      {loading ? <p>{__('Loading...', 'flex-content-scheduler')}</p> : null}
      {message ? <p className={`flex-cs-message ${message.type}`}>{message.text}</p> : null}
      {countdown ? <p className="flex-cs-countdown">{countdown}</p> : null}

      <label>
        {__('Expiry Date', 'flex-content-scheduler')}
        <input
          type="datetime-local"
          value={schedule.expiry_date}
          onChange={(e) => setSchedule({ ...schedule, expiry_date: e.target.value })}
        />
      </label>

      <label>
        {__('Action', 'flex-content-scheduler')}
        <select
          value={schedule.expiry_action}
          onChange={(e) => setSchedule({ ...schedule, expiry_action: e.target.value })}
        >
          <option value="unpublish">{__('Unpublish', 'flex-content-scheduler')}</option>
          <option value="delete">{__('Delete', 'flex-content-scheduler')}</option>
          <option value="redirect">{__('Redirect', 'flex-content-scheduler')}</option>
          <option value="change_status">{__('Change status', 'flex-content-scheduler')}</option>
        </select>
      </label>

      {schedule.expiry_action === 'redirect' ? (
        <label>
          {__('Redirect URL', 'flex-content-scheduler')}
          <input
            type="url"
            value={schedule.redirect_url}
            onChange={(e) => setSchedule({ ...schedule, redirect_url: e.target.value })}
          />
        </label>
      ) : null}

      {schedule.expiry_action === 'change_status' ? (
        <label>
          {__('New status', 'flex-content-scheduler')}
          <select
            value={schedule.new_status}
            onChange={(e) => setSchedule({ ...schedule, new_status: e.target.value })}
          >
            <option value="draft">{__('Draft', 'flex-content-scheduler')}</option>
            <option value="private">{__('Private', 'flex-content-scheduler')}</option>
            <option value="pending">{__('Pending', 'flex-content-scheduler')}</option>
            <option value="publish">{__('Publish', 'flex-content-scheduler')}</option>
          </select>
        </label>
      ) : null}

      <div className="flex-cs-actions">
        <button type="button" className="button button-primary" disabled={saving} onClick={submit}>
          {saving ? __('Saving...', 'flex-content-scheduler') : existing ? __('Update', 'flex-content-scheduler') : __('Save', 'flex-content-scheduler')}
        </button>
        {existing ? (
          <button type="button" className="button" disabled={saving} onClick={remove}>
            {__('Delete', 'flex-content-scheduler')}
          </button>
        ) : null}
      </div>
    </div>
  );
}
