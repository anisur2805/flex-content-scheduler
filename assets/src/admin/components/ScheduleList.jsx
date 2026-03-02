import { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Paginated list component displaying scheduled content expiry entries.
 *
 * @since 1.0.0
 *
 * @param {Object} props              Component props.
 * @param {number} props.refreshToken Token that triggers data re-fetch when changed.
 * @return {JSX.Element} Schedule list UI.
 */
export default function ScheduleList({ refreshToken }) {
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(false);
  const [page, setPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [filters, setFilters] = useState({ post_type: '', status: '' });

  useEffect(() => {
    setLoading(true);
    const query = new URLSearchParams({ page: String(page), per_page: '20', ...filters });
    apiFetch({ path: `/flex-cs/v1/schedules?${query.toString()}`, headers: { 'X-WP-Nonce': window.flexCSAdmin?.nonce }, parse: false })
      .then(async (res) => {
        const totalHeader = res.headers.get('X-WP-Total');
        if (totalHeader) {
          setTotal(Number(totalHeader));
        }
        const data = await res.json();
        setItems(Array.isArray(data) ? data : []);
      })
      .catch(() => {
        setItems([]);
        setTotal(0);
      })
      .finally(() => setLoading(false));
  }, [page, filters, refreshToken]);

  return (
    <section>
      <h2>{__('Scheduled Content', 'flex-content-scheduler')}</h2>
      <div className="flex-cs-filters">
        <select value={filters.post_type} onChange={(e) => setFilters({ ...filters, post_type: e.target.value })}>
          <option value="">{__('All post types', 'flex-content-scheduler')}</option>
          {(window.flexCSAdmin?.postTypes || []).map((t) => (
            <option key={t.slug} value={t.slug}>{t.label}</option>
          ))}
        </select>
        <select value={filters.status} onChange={(e) => setFilters({ ...filters, status: e.target.value })}>
          <option value="">{__('All statuses', 'flex-content-scheduler')}</option>
          <option value="0">{__('Pending', 'flex-content-scheduler')}</option>
          <option value="1">{__('Processed', 'flex-content-scheduler')}</option>
        </select>
      </div>

      {loading ? <p>{__('Loading schedules...', 'flex-content-scheduler')}</p> : null}

      <table className="widefat striped">
        <thead>
          <tr>
            <th>{__('Post', 'flex-content-scheduler')}</th>
            <th>{__('Post Type', 'flex-content-scheduler')}</th>
            <th>{__('Expiry Date (UTC)', 'flex-content-scheduler')}</th>
            <th>{__('Action', 'flex-content-scheduler')}</th>
            <th>{__('Status', 'flex-content-scheduler')}</th>
          </tr>
        </thead>
        <tbody>
          {items.map((item) => (
            <tr key={item.id}>
              <td>{item.post_title || `#${item.post_id}`}</td>
              <td>{item.post_type || '-'}</td>
              <td>{item.expiry_date}</td>
              <td>{item.expiry_action}</td>
              <td>{Number(item.is_processed) === 1 ? __('Processed', 'flex-content-scheduler') : __('Pending', 'flex-content-scheduler')}</td>
            </tr>
          ))}
          {!items.length ? (
            <tr>
              <td colSpan="5">{__('No schedules found.', 'flex-content-scheduler')}</td>
            </tr>
          ) : null}
        </tbody>
      </table>

      <div className="flex-cs-pagination">
        <button type="button" className="button" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
          {__('Previous', 'flex-content-scheduler')}
        </button>
        <span>{__('Page', 'flex-content-scheduler')} {page} {total > 0 ? `(${total} ${__('total', 'flex-content-scheduler')})` : ''}</span>
        <button type="button" className="button" disabled={items.length < 20 || (total > 0 && page * 20 >= total)} onClick={() => setPage((p) => p + 1)}>
          {__('Next', 'flex-content-scheduler')}
        </button>
      </div>
    </section>
  );
}

ScheduleList.propTypes = {
  refreshToken: PropTypes.number.isRequired
};
