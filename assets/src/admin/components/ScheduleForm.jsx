import { useState } from 'react';
import PropTypes from 'prop-types';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Form component for creating new content expiry schedules.
 *
 * @since 1.0.0
 *
 * @param {Object}   props         Component props.
 * @param {Function} props.onSaved Callback fired after a schedule is successfully created.
 * @return {JSX.Element} Schedule form UI.
 */
export default function ScheduleForm({ onSaved }) {
  const [postId, setPostId] = useState('');
  const [expiryDate, setExpiryDate] = useState('');
  const [action, setAction] = useState('unpublish');
  const [redirectUrl, setRedirectUrl] = useState('');
  const [newStatus, setNewStatus] = useState('draft');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');

  const submit = async (e) => {
    e.preventDefault();
    setSaving(true);
    setError('');

    const payload = {
      post_id: Number(postId),
      expiry_date: expiryDate ? `${expiryDate.replace('T', ' ')}:00` : '',
      expiry_action: action,
      redirect_url: redirectUrl,
      new_status: newStatus
    };

    try {
      await apiFetch({ path: '/flex-cs/v1/schedules', method: 'POST', data: payload, headers: { 'X-WP-Nonce': window.flexCSAdmin?.nonce } });
      setPostId('');
      setExpiryDate('');
      setRedirectUrl('');
      setError('');
      onSaved?.();
    } catch (err) {
      setError(err?.message || __('Failed to create schedule. Please try again.', 'flex-content-scheduler'));
    } finally {
      setSaving(false);
    }
  };

  return (
    <section>
      <h2>{__('Add Schedule', 'flex-content-scheduler')}</h2>
      {error && <div className="notice notice-error"><p>{error}</p></div>}
      <form className="flex-cs-form" onSubmit={submit}>
        <input type="number" placeholder={__('Post ID', 'flex-content-scheduler')} value={postId} onChange={(e) => setPostId(e.target.value)} required />
        <input type="datetime-local" value={expiryDate} onChange={(e) => setExpiryDate(e.target.value)} required />
        <select value={action} onChange={(e) => setAction(e.target.value)}>
          <option value="unpublish">{__('Unpublish', 'flex-content-scheduler')}</option>
          <option value="delete">{__('Delete', 'flex-content-scheduler')}</option>
          <option value="redirect">{__('Redirect', 'flex-content-scheduler')}</option>
          <option value="change_status">{__('Change status', 'flex-content-scheduler')}</option>
          <option value="sticky">{__('Sticky', 'flex-content-scheduler')}</option>
          <option value="unsticky">{__('Unsticky', 'flex-content-scheduler')}</option>
        </select>

        {action === 'redirect' ? (
          <input type="url" placeholder={__('Redirect URL', 'flex-content-scheduler')} value={redirectUrl} onChange={(e) => setRedirectUrl(e.target.value)} />
        ) : null}

        {action === 'change_status' ? (
          <select value={newStatus} onChange={(e) => setNewStatus(e.target.value)}>
            <option value="draft">{__('Draft', 'flex-content-scheduler')}</option>
            <option value="private">{__('Private', 'flex-content-scheduler')}</option>
            <option value="pending">{__('Pending', 'flex-content-scheduler')}</option>
            <option value="publish">{__('Publish', 'flex-content-scheduler')}</option>
          </select>
        ) : null}

        <button type="submit" className="button button-primary" disabled={saving}>
          {saving ? __('Saving...', 'flex-content-scheduler') : __('Create', 'flex-content-scheduler')}
        </button>
      </form>
    </section>
  );
}

ScheduleForm.propTypes = {
  onSaved: PropTypes.func
};
