import { useEffect, useMemo, useState } from 'react';

const initialState = {
  expiry_date: '',
  expiry_action: 'unpublish',
  redirect_url: '',
  new_status: 'draft'
};

function toLocalInput(utcDate) {
  if (!utcDate) return '';
  const d = new Date(utcDate.replace(' ', 'T') + 'Z');
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function toUtcString(localValue) {
  if (!localValue) return '';
  const d = new Date(localValue);
  const pad = (n) => String(n).padStart(2, '0');
  return `${d.getUTCFullYear()}-${pad(d.getUTCMonth() + 1)}-${pad(d.getUTCDate())} ${pad(d.getUTCHours())}:${pad(d.getUTCMinutes())}:00`;
}

function parsePossiblyPollutedJson(rawText) {
  const text = String(rawText || '').trim();

  if (!text) return null;
  if (text === 'null') return null;

  try {
    return JSON.parse(text);
  } catch (e) {
    // Continue to fallback extraction for hosts that leak warnings before JSON.
  }

  const candidates = [];
  const firstObject = text.indexOf('{');
  const lastObject = text.lastIndexOf('}');
  if (firstObject !== -1 && lastObject > firstObject) {
    candidates.push(text.slice(firstObject, lastObject + 1));
  }

  const firstArray = text.indexOf('[');
  const lastArray = text.lastIndexOf(']');
  if (firstArray !== -1 && lastArray > firstArray) {
    candidates.push(text.slice(firstArray, lastArray + 1));
  }

  for (const candidate of candidates) {
    try {
      return JSON.parse(candidate);
    } catch (e) {
      // Try the next candidate.
    }
  }

  throw new Error('The response is not a valid JSON response.');
}

async function request(endpoint, { method = 'GET', data } = {}) {
  const base = (window.flexCSMetabox?.restUrl || '').replace(/\/$/, '');
  const url = `${base}${endpoint}`;

  const response = await fetch(url, {
    method,
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': window.flexCSMetabox?.nonce || ''
    },
    body: data ? JSON.stringify(data) : undefined
  });

  const raw = await response.text();
  const parsed = parsePossiblyPollutedJson(raw);

  if (!response.ok) {
    const message = parsed?.message || `Request failed (${response.status})`;
    throw new Error(message);
  }

  return parsed;
}

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
    request(`/schedules/post/${postId}`)
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
    if (delta <= 0) return 'Expired';
    const days = Math.floor(delta / (1000 * 60 * 60 * 24));
    const hours = Math.floor((delta % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    return `Expires in ${days} days, ${hours} hours`;
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
      const path = existing ? `/schedules/${existing.id}` : '/schedules';
      const response = await request(path, { method, data: payload });
      setExisting(response);
      setMessage({ type: 'success', text: 'Schedule saved.' });
    } catch (error) {
      const text = error?.message ? `Failed to save schedule: ${error.message}` : 'Failed to save schedule.';
      setMessage({ type: 'error', text });
    } finally {
      setSaving(false);
    }
  };

  const remove = async () => {
    if (!existing?.id) return;
    setSaving(true);
    try {
      await request(`/schedules/${existing.id}`, { method: 'DELETE' });
      setExisting(null);
      setSchedule(initialState);
      setMessage({ type: 'success', text: 'Schedule deleted.' });
    } catch (error) {
      const text = error?.message ? `Failed deleting schedule: ${error.message}` : 'Failed deleting schedule.';
      setMessage({ type: 'error', text });
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="fcs-metabox">
      {loading ? <p>Loading...</p> : null}
      {message ? <p className={`fcs-message ${message.type}`}>{message.text}</p> : null}
      {countdown ? <p className="fcs-countdown">{countdown}</p> : null}

      <label>
        Expiry Date
        <input
          type="datetime-local"
          value={schedule.expiry_date}
          onChange={(e) => setSchedule({ ...schedule, expiry_date: e.target.value })}
        />
      </label>

      <label>
        Action
        <select
          value={schedule.expiry_action}
          onChange={(e) => setSchedule({ ...schedule, expiry_action: e.target.value })}
        >
          <option value="unpublish">Unpublish</option>
          <option value="delete">Delete</option>
          <option value="redirect">Redirect</option>
          <option value="change_status">Change status</option>
        </select>
      </label>

      {schedule.expiry_action === 'redirect' ? (
        <label>
          Redirect URL
          <input
            type="url"
            value={schedule.redirect_url}
            onChange={(e) => setSchedule({ ...schedule, redirect_url: e.target.value })}
          />
        </label>
      ) : null}

      {schedule.expiry_action === 'change_status' ? (
        <label>
          New status
          <select
            value={schedule.new_status}
            onChange={(e) => setSchedule({ ...schedule, new_status: e.target.value })}
          >
            <option value="draft">Draft</option>
            <option value="private">Private</option>
            <option value="pending">Pending</option>
            <option value="publish">Publish</option>
          </select>
        </label>
      ) : null}

      <div className="fcs-actions">
        <button type="button" className="button button-primary" disabled={saving} onClick={submit}>
          {saving ? 'Saving...' : existing ? 'Update' : 'Save'}
        </button>
        {existing ? (
          <button type="button" className="button" disabled={saving} onClick={remove}>
            Delete
          </button>
        ) : null}
      </div>
    </div>
  );
}
