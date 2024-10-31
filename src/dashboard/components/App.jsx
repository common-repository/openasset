/**
 * External dependencies.
 */
import { Route, Routes } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';

/**
 * Internal dependencies.
 */
import { Dashboard } from './pages';
import { GeneralSettings, DataOptions } from './pages/settings';

const App = () => {
    return (
        <>
            <Toaster position="bottom-center" />
            <Routes>
                <Route path="/dashboard" element={<Dashboard />} />
                <Route path="/settings" element={<GeneralSettings />} />
                <Route path="/data-options" element={<DataOptions />} />
            </Routes>
        </>
    )
}

export default App;