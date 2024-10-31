/**
 * External dependencies.
 */
import clsx from 'clsx';

const TextInput = ({ id, label, type = 'text', required = false, description, value, className, order = '', options, setOption, disabled = false, ...props }) => {

    const handleChange = e => {
        if (!disabled) {
            setOption(e.target.value, id);
        }
    };

    return (
        <fieldset className={order}>
            <legend className='text-sm font-semibold leading-6 text-gray-900'>
                {label && label}
            </legend>
            <p className='mt-1 text-sm leading-6 text-gray-600'>
                {description && description}
            </p>
            <input
                type={type}
                name={id}
                id={id}
                className={clsx(
                    "block w-full rounded-md border-0 mt-3 px-2.5 py-1.5 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-gray-600 sm:text-sm sm:leading-6",
                    disabled ? "text-gray-500 bg-gray-100 cursor-not-allowed" : "text-gray-900",
                    className && className
                )}
                onChange={handleChange}
                value={value}
                disabled={disabled}
                {...props}
                required={required}
                autoComplete='off'
                data-lpignore='true'
                data-form-type='other'
            />
        </fieldset>
    );
}

export default TextInput;
