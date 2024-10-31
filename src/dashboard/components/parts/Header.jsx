/**
 * External dependencies.
 */
import { NavLink } from 'react-router-dom'
import { Disclosure } from '@headlessui/react'
import classNames from 'clsx';

/**
 * Internal dependencies.
 */
const pluginState = window.openAssetPluginState;

const Header = ({ navigation, secondaryNav = null }) => {
    return (
		<Disclosure
			as='nav'
			className='bg-white relative sm:sticky sm:top-[32px] sm:z-50'
		>
			{({ open }) => (
				<>
					<div className='mx-auto max-w-6xl px-4 sm:px-6 lg:px-8'>
						<div className='flex flex-shrink-0 items-center justify-between pt-6 pb-4'>
							<div className='flex gap-2 items-center'>
								<img
									className='block h-9 w-auto'
									src={
										pluginState.assetsURL +
										"/img/openasset.svg"
									}
									alt='OpenAsset'
								/>
								<h1 className='text-3xl font-bold leading-tight tracking-tight text-gray-900'>
									OpenAsset
								</h1>
							</div>
						</div>
						<div className='flex justify-between border-b border-solid border-border-primary'>
							{navigation && (
								<div className='flex'>
									<div className='flex space-x-2'>
										{navigation.map((item) => (
											<NavLink
												key={item.name}
												to={item.href}
												className={({ isActive }) =>
													classNames(
														isActive
															? ""
															: "bg-[#DCDCDE] text-[#aeaeae]",
														"inline-flex items-center border-solid border border-border-primary p-2 text-base shadow-none border-b-2 text-[#51575D]"
													)
												}
											>
												{item.name}
											</NavLink>
										))}
									</div>
								</div>
							)}
							{secondaryNav && (
								<div className='flex'>
									<div className='hidden sm:-my-px sm:ml-6 sm:flex sm:space-x-6'>
										{secondaryNav.map((item) => (
											<NavLink
												key={item.name}
												to={item.href}
												className='inline-flex items-center px-1.5 pt-1 text-base shadow-none text-gray-500 hover:text-gray-800'
											>
												{item.icon && (
													<item.icon className='h-4 w-4 mr-1.5 ' />
												)}
												{item.name}
											</NavLink>
										))}
									</div>
								</div>
							)}
						</div>
					</div>
				</>
			)}
		</Disclosure>
	);
}

export default Header;