import { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';

export default function ScheduleForm({ onSaved }) {
  const [postId, setPostId] = useState('');
  const [expiryDate, setExpiryDate] = useState('');
  const [action, setAction] = useState('unpublish');
  const [redirectUrl, setRedirectUrl] = useState('');
  const [newStatus, setNewStatus] = useState('draft');
  const [saving, setSaving] = useState(false);

  const submit = async (e) => {
    e.preventDefault();
    setSaving(true);

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
      onSaved?.();
    } finally {
      setSaving(false);
    }
  };

  return (
    <section>
      <h2>Add Schedule</h2>
      <form className="flex-cs-form" onSubmit={submit}>
        <input type="number" placeholder="Post ID" value={postId} onChange={(e) => setPostId(e.target.value)} required />
        <input type="datetime-local" value={expiryDate} onChange={(e) => setExpiryDate(e.target.value)} required />
        <select value={action} onChange={(e) => setAction(e.target.value)}>
          <option value="unpublish">Unpublish</option>
          <option value="delete">Delete</option>
          <option value="redirect">Redirect</option>
          <option value="change_status">Change status</option>
        </select>

        {action === 'redirect' ? (
          <input type="url" placeholder="Redirect URL" value={redirectUrl} onChange={(e) => setRedirectUrl(e.target.value)} />
        ) : null}

        {action === 'change_status' ? (
          <select value={newStatus} onChange={(e) => setNewStatus(e.target.value)}>
            <option value="draft">Draft</option>
            <option value="private">Private</option>
            <option value="pending">Pending</option>
            <option value="publish">Publish</option>
          </select>
        ) : null}

        <button type="submit" className="button button-primary" disabled={saving}>
          {saving ? 'Saving...' : 'Create'}
        </button>
      </form>
    </section>
  );
}
