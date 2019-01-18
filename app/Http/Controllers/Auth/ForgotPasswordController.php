<?php
/**
 * webtrees: online genealogy
 * Copyright (C) 2019 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Fisharebest\Webtrees\Http\Controllers\Auth;

use Fisharebest\Webtrees\FlashMessages;
use Fisharebest\Webtrees\Http\Controllers\AbstractBaseController;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Log;
use Fisharebest\Webtrees\Mail;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\User;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for requesting password resets.
 */
class ForgotPasswordController extends AbstractBaseController
{
    /**
     * Show a password reset page.
     *
     * @return Response
     */
    public function forgotPasswordPage(): Response
    {
        $title = I18N::translate('Request a new password');

        return $this->viewResponse('forgot-password-page', [
            'title' => $title,
        ]);
    }

    /**
     * Send a password reset email.
     *
     * @param Request $request
     * @param Tree    $tree
     *
     * @return RedirectResponse
     */
    public function forgotPasswordAction(Request $request, Tree $tree): RedirectResponse
    {
        $identifier = $request->get('identifier', '');

        $user = User::findByIdentifier($identifier);

        if ($user instanceof User) {
            $password = $this->createNewPassword();
            $user->setPassword($password);

            Log::addAuthenticationLog('Password request was sent to user: ' . $user->getUserName());

            Mail::send(
                User::userFromTree($tree),
                $user,
                User::userFromTree($tree),
                I18N::translate('Lost password request'),
                view('emails/password-reset-text', [
                    'user'         => $user,
                    'new_password' => $password,
                ]), view('emails/password-reset-html', [
                    'user'         => $user,
                    'new_password' => $password,
                ])
            );

            FlashMessages::addMessage(I18N::translate('A new password has been created and emailed to %s. You can change this password after you sign in.', e($identifier)), 'success');

            return new RedirectResponse(route('login', ['username' => $user->getUserName()]));
        }

        FlashMessages::addMessage(I18N::translate('There is no account with the username or email “%s”.', e($identifier)), 'danger');

        return new RedirectResponse(route('forgot-password'));
    }

    /**
     * @return string
     */
    private function createNewPassword(): string
    {
        return Str::random(8);
    }
}
