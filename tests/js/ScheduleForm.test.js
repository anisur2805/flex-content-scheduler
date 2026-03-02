import React from 'react';
import { render, screen } from '@testing-library/react';
import ScheduleForm from '../../assets/src/admin/components/ScheduleForm';

jest.mock('@wordpress/api-fetch', () => jest.fn());
jest.mock('@wordpress/i18n', () => ({ __: (s) => s }));

describe('ScheduleForm', () => {
  test('renders create button', () => {
    render(<ScheduleForm onSaved={() => {}} />);
    expect(screen.getByText('Create')).toBeInTheDocument();
  });
});
