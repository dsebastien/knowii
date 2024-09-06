import { useRoute } from 'ziggy-js';
import { FormEventHandler, useRef, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { DELETE_USER_URL, DESTROY_OTHER_BROWSER_SESSIONS_URL } from '@knowii/common';
import { Button } from 'primereact/button';
import { FaLock } from 'react-icons/fa';
import { InputText } from 'primereact/inputtext';
import InputError from '@/Components/InputError';
import ActionSection from '@/Components/ActionSection';
import { Dialog } from 'primereact/dialog';

interface DeleteUserFormProps {
  password: string;
}

export default function DeleteUserForm() {
  const route = useRoute();

  const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);

  const form = useForm<DeleteUserFormProps>({
    password: '',
  });

  const passwordRef = useRef<HTMLInputElement | null>(null);

  const confirmUserDeletion: FormEventHandler = () => {
    setConfirmingUserDeletion(true);

    setTimeout(() => passwordRef.current?.focus(), 250);
  };

  const deleteUser: FormEventHandler = (e) => {
    e.preventDefault();

    form.delete(route(DELETE_USER_URL), {
      preserveScroll: true,
      onSuccess: () => closeModal(),
      onError: () => passwordRef.current?.focus(),
      onFinish: () => form.reset(),
    });
  };

  const logoutOtherBrowserSessions = () => {
    form.delete(route(DESTROY_OTHER_BROWSER_SESSIONS_URL), {
      preserveScroll: true,
      // FIXME implement
      onSuccess: () => closeModal(),
      onError: () => passwordRef.current?.focus(),
      onFinish: () => form.reset(),
    });
  };

  const closeModal = () => {
    setConfirmingUserDeletion(false);
    form.reset();
  };

  return (
    <ActionSection title={'Delete Account'} description={'Permanently delete your account.'}>
      <div className="max-w-xl text-sm text-gray-600">
        Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please
        download any data or information that you wish to retain.
      </div>

      <div className="flex items-center mt-5">
        <Button severity="danger" onClick={confirmUserDeletion}>
          Delete Account
        </Button>
      </div>

      <Dialog
        header="Are you sure you want to delete your account?"
        visible={confirmingUserDeletion}
        className="w-full sm:w-[75vw] md:w-[60vw] lg:w-[40vw] xl:w-[30vw]"
        onHide={() => closeModal()}
        footer={
          <>
            <Button severity="secondary" label="Cancel" onClick={closeModal} />
            <Button severity="danger" label="Go ahead!" onClick={deleteUser} disabled={form.processing} className="ml-2" />
          </>
        }
      >
        Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you
        would like to permanently delete your account.
        <div className="mt-4">
          <div className="p-inputgroup mt-1">
            <span className="p-inputgroup-addon mt-1">
              <FaLock />
            </span>
            <InputText
              type="password"
              className="mt-1 w-full"
              ref={passwordRef}
              value={form.data.password}
              onChange={(e) => form.setData('password', e.target.value)}
              required
              autoComplete="current-password"
            />
          </div>
          <InputError className="mt-2" message={form.errors.password} />
        </div>
      </Dialog>
    </ActionSection>
  );
}
