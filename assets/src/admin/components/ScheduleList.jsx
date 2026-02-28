import { useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';

export default function ScheduleList({ refreshToken }) {
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(false);
  const [page, setPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [filters, setFilters] = useState({ post_type: '', status: '' });

  useEffect(() => {
    setLoading(true);
    const query = new URLSearchParams({ page: String(page), per_page: '20', ...filters });
    apiFetch({ path: `/flex-cs/v1/schedules?${query.toString()}`, headers: { 'X-WP-Nonce': window.flexCSAdmin?.nonce } })
      .then((res) => {
        setItems(Array.isArray(res) ? res : []);
      })
      .catch(() => setItems([]))
      .finally(() => setLoading(false));
  }, [page, filters, refreshToken]);

  return (
    <section>
      <h2>Scheduled Content</h2>
      <div className="flex-cs-filters">
        <select value={filters.post_type} onChange={(e) => setFilters({ ...filters, post_type: e.target.value })}>
          <option value="">All post types</option>
          {(window.flexCSAdmin?.postTypes || []).map((t) => (
            <option key={t.slug} value={t.slug}>{t.label}</option>
          ))}
        </select>
        <select value={filters.status} onChange={(e) => setFilters({ ...filters, status: e.target.value })}>
          <option value="">All statuses</option>
          <option value="0">Pending</option>
          <option value="1">Processed</option>
        </select>
      </div>

      {loading ? <p>Loading schedules...</p> : null}

      <table className="widefat striped">
        <thead>
          <tr>
            <th>Post</th>
            <th>Post Type</th>
            <th>Expiry Date (UTC)</th>
            <th>Action</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          {items.map((item) => (
            <tr key={item.id}>
              <td>{item.post_title || `#${item.post_id}`}</td>
              <td>{item.post_type || '-'}</td>
              <td>{item.expiry_date}</td>
              <td>{item.expiry_action}</td>
              <td>{Number(item.is_processed) === 1 ? 'Processed' : 'Pending'}</td>
            </tr>
          ))}
          {!items.length ? (
            <tr>
              <td colSpan="5">No schedules found.</td>
            </tr>
          ) : null}
        </tbody>
      </table>

      <div className="flex-cs-pagination">
        <button type="button" className="button" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
          Previous
        </button>
        <span>Page {page}</span>
        <button type="button" className="button" onClick={() => setPage((p) => p + 1)}>
          Next
        </button>
      </div>
    </section>
  );
}
