import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import ScheduleList from '../../assets/src/admin/components/ScheduleList';

jest.mock('@wordpress/api-fetch', () => jest.fn(() => Promise.resolve({
  headers: { get: () => '0' },
  json: async () => []
})));
jest.mock('@wordpress/i18n', () => ({ __: (s) => s }));

describe('ScheduleList', () => {
  test('renders heading', async () => {
    window.flexCSAdmin = { nonce: 'valid-nonce', postTypes: [] };
    render(<ScheduleList refreshToken={0} />);
    await waitFor(() => {
      expect(screen.getByText('Scheduled Content')).toBeInTheDocument();
    });
  });
});
