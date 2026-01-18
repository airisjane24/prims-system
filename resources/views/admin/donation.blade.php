<x-app-layout>
    <div class="py-12 bg-white h-full mt-4 rounded-2xl">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden">
                <div class="p-1 text-gray-900">
                    @if (session('success'))
                        <div class="toast" id="success">
                            <div class="alert alert-info bg-green-500 text-white">
                                <span>{{ session('success') }}</span>
                            </div>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="toast" id="error">
                            <div class="alert alert-danger bg-red-500 text-white">
                                <span>{{ session('error') }}</span>
                            </div>
                        </div>
                    @endif
                    <div class="mb-4">
                        <form action="{{ route('donations') }}" method="GET" class="flex items-center"
                            id="searchForm">
                            <input type="text" placeholder="Search Donation....."
                                class="input input-bordered w-full max-w-xs" />
                            <button type="submit"
                                class="ml-2 bg-green-500 text-white rounded-md px-4 py-3 hover:bg-green-600">
                                Search
                            </button>
                        </form>
                    </div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Donations List</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 table-auto">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Donor Name</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Amount</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date and Time</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 p-3">
                                @foreach ($donations as $donation)
                                    <tr class="cursor-pointer" onclick="viewModal{{ $donation->id }}.showModal()">
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $donation->donor_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $donation->amount }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
    {{ \Carbon\Carbon::parse($donation->created_at)->setTimezone('Asia/Manila')->format('F d, Y g:i A') }}

</td>

                                        <td class="px-6 py-4 whitespace-nowrap">{{ $donation->status }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">

                                        <!-- <form action="{{ route('donation.updateStatus', $donation->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status" value="Received">

                                            <button type="submit" class="hover:bg-green-100 p-3 rounded-md">
                                                <img src="{{ asset('assets/img/save.svg') }}" class="w-[22px] h-[22px]">
                                            </button>
                                        </form> -->
                                        

                                            <button title="Update Status"
    class="hover:bg-green-100 p-3 rounded-md"
    onclick="event.stopPropagation(); updateStatus{{ $donation->id }}.showModal()">
    <img src="{{ asset('assets/img/save.svg') }}" class="w-[22px] h-[22px]">
</button>

<button title="View Donation"
    class="hover:bg-blue-100 p-3 rounded-md"
    onclick="event.stopPropagation(); editModal{{ $donation->id }}.showModal()">
    <img src="{{ asset('assets\img\eye.jpg') }}" class="w-[20px] h-[20px]">
</button>
</td>
                                    </tr>

                                    <!-- View Modal -->
                                    <dialog id="viewModal{{ $donation->id }}" class="modal">
                                        <div class="modal-box rounded-lg shadow-lg">
                                            <h3 class="text-lg font-bold mb-4">View Donation</h3>
                                            <hr class="my-4">
                                            <div class="mb-4">
                                                <label class="input input-bordered flex items-center gap-2">
                                                    Donor Name:
                                                    <input type="text" name="donor_name"
                                                        class="grow border-none focus:ring-0 focus:border-none"
                                                        value="{{ $donation->donor_name }}" readonly />
                                                </label>
                                            </div>
                                            <div class="mb-4">
                                                <label class="input input-bordered flex items-center gap-2">
                                                    Donor Email:
                                                    <input type="text" name="donor_email"
                                                        class="grow border-none focus:ring-0 focus:border-none"
                                                        value="{{ $donation->donor_email }}" readonly />
                                                </label>
                                            </div>
                                            <div class="mb-4">
                                                <label class="input input-bordered flex items-center gap-2">
                                                    Donor Phone:
                                                    <input type="text" name="donor_phone"
                                                        class="grow border-none focus:ring-0 focus:border-none"
                                                        value="{{ $donation->donor_phone }}" readonly />
                                                </label>
                                            </div>
                                            <div class="mb-4">
                                                <label class="input input-bordered flex items-center gap-2">
                                                    Amount:
                                                    <input type="text" name="amount"
                                                        class="grow border-none focus:ring-0 focus:border-none"
                                                        value="{{ $donation->amount }}" readonly />
                                                </label>
                                            </div>
                                            <div class="mb-4">
                                                <label class="input input-bordered flex items-center gap-2">
                                                    Date:
                                                    <input type="text" name="donation_date"
                                                        class="grow border-none focus:ring-0 focus:border-none"
                                                        value="{{ $donation->donation_date }}" readonly />
                                                </label>
                                            </div>
                                            <div class="mb-4">
                                                <label class="input input-bordered flex items-center gap-2">
                                                    Note:
                                                    <input type="text" name="note"
                                                        class="grow border-none focus:ring-0 focus:border-none"
                                                        value="{{ $donation->note }}" readonly />
                                                </label>
                                            </div>
                                            <!-- Transaction File Preview -->
<div class="mb-4">
    <label class="block text-gray-700 font-medium">Transaction File</label>
     @if($donation->transaction_id)
            <img src="{{ asset('assets/transactions/' . $donation->transaction_id) }}" 
                 alt="Transaction Image" 
                 class="max-w-full h-auto border rounded" />
        @else
            <span class="text-gray-500">No transaction image uploaded</span>
        @endif
</div>

                                            <hr class="my-4">
                                            <div class="flex justify-end">
                                                <button class="btn text-black hover:bg-red-700 hover:text-white"
                                                    type="button"
                                                    onclick="viewModal{{ $donation->id }}.close()">Close</button>
                                            </div>
                                        </div>
                                    </dialog>

                                    <!-- Update status Modal -->
                                    <dialog id="updateStatus{{ $donation->id }}" class="modal">
                                        <div class="modal-box rounded-lg shadow-lg">
                                            <hr class="my-4">
                                            <form action="{{ route('donation.updateStatus', $donation->id) }}"
                                                method="POST" enctype="multipart/form-data" id="updateStatus">
                                                @csrf
                                                @method('PUT')
                                                <div class="mb-4">
                                                    <label class="block text-gray-700 font-medium">Receive Donation?</label>
                                                    <input type="hidden" name="status"
                                                        placeholder="Receive Donation?"
                                                        value="Received"
                                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500 focus:border-blue-500 p-3 transition duration-150 ease-in-out"
                                                        required>
                                                </div>
                                                <hr class="my-4">
                                                <div class="flex justify-end">
                                                    <button class="btn btn-primary text-white me-2"
                                                        type="submit">Receive</button>
                                                    <button class="btn text-black hover:bg-red-700 hover:text-white"
                                                        type="button"
                                                        onclick="updateStatus{{ $donation->id }}.close()">Close</button>
                                                </div>
                                            </form>
                                        </div>
                                    </dialog>

                                    <!-- View (Read-Only) Donation Modal -->
<dialog id="editModal{{ $donation->id }}" class="modal">
    <div class="modal-box rounded-lg shadow-lg">
        <h3 class="text-lg font-bold mb-4">View Donation</h3>
        <hr class="my-4">
        <form>
            <div class="mb-4">
                <label class="block text-gray-700 font-medium">Donor Name</label>
                <input type="text" value="{{ $donation->donor_name }}" readonly
                    class="mt-1 block w-full border border-gray-300 rounded-md p-3 bg-gray-100">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-medium">Donor Email</label>
                <input type="email" value="{{ $donation->donor_email }}" readonly
                    class="mt-1 block w-full border border-gray-300 rounded-md p-3 bg-gray-100">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-medium">Donor Phone</label>
                <input type="text" value="{{ $donation->donor_phone }}" readonly
                    class="mt-1 block w-full border border-gray-300 rounded-md p-3 bg-gray-100">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-medium">Amount</label>
                <input type="number" value="{{ $donation->amount }}" readonly
                    class="mt-1 block w-full border border-gray-300 rounded-md p-3 bg-gray-100">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-medium">Date</label>
                <input type="text" value="{{ \Carbon\Carbon::parse($donation->donation_date)->format('F d, Y') }}" readonly
                    class="mt-1 block w-full border border-gray-300 rounded-md p-3 bg-gray-100">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-medium">Note</label>
                <input type="text" value="{{ $donation->note }}" readonly
                    class="mt-1 block w-full border border-gray-300 rounded-md p-3 bg-gray-100">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-medium">Status</label>
                <input type="text" value="{{ $donation->status }}" readonly
                    class="mt-1 block w-full border border-gray-300 rounded-md p-3 bg-gray-100">
            </div>

            <!-- Transaction File Preview -->
<div class="mb-4">
    <label class="block text-gray-700 font-medium">Transaction File</label>
    @if($donation->transaction_id)
            <img src="{{ asset('assets/transactions/' . $donation->transaction_id) }}" 
                 alt="Transaction Image" 
                 class="max-w-full h-auto border rounded" />
        @else
            <span class="text-gray-500">No transaction image uploaded</span>
        @endif
</div>


            <hr class="my-4">
            <div class="flex justify-end">
                <button class="btn text-black hover:bg-red-700 hover:text-white" type="button"
                    onclick="editModal{{ $donation->id }}.close()">Close</button>
            </div>
        </form>
    </div>
</dialog>


                                    <!-- Delete Modal -->
                                    <dialog id="destroyModal{{ $donation->id }}" class="modal">
                                        <div class="modal-box rounded-lg shadow-lg">
                                            <h3 class="text-lg font-semibold mb-4">Delete Donation</h3>
                                            <p>Are you sure you want to delete this donation?</p>
                                            <div class="flex justify-end mt-4">
                                                <form action="{{ route('donation.destroy', $donation->id) }}"
                                                    method="POST" id="deleteForm">
                                                    @csrf
                                                    @method('DELETE')
                                                    <hr class="my-4">
                                                    <button class="btn bg-red-700 hover:bg-red-800 text-white me-2"
                                                        type="submit">Delete</button>
                                                    <button class="btn text-black hover:bg-red-700 hover:text-white"
                                                        type="button"
                                                        onclick="destroyModal{{ $donation->id }}.close()">Close</button>
                                                </form>
                                            </div>
                                        </div>
                                    </dialog>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Modal -->
        <dialog id="addModal" class="modal">
            <div class="modal-box rounded-lg shadow-lg">
                <h3 class="text-lg font-bold mb-4">Add Donation</h3>
                <hr class="my-4">
                <form action="{{ route('donation.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium">Donor Type</label>
                        <select name="donor_type"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500 focus:border-blue-500 p-3 transition duration-150 ease-in-out"
                            required>
                            <option value="">Select Donor Type</option>
                            <option value="Individual">Individual</option>
                            <option value="Organization">Organization</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium">Donor Name</label>
                        <input type="text" name="donor_name" placeholder="Enter donor name"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500 focus:border-blue-500 p-3 transition duration-150 ease-in-out"
                            required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium">Donor Email</label>
                        <input type="email" name="donor_email" placeholder="Enter donor email"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500 focus:border-blue-500 p-3 transition duration-150 ease-in-out">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium">Donor Phone</label>
                        <input type="text" name="donor_phone" placeholder="Enter donor phone"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500 focus:border-blue-500 p-3 transition duration-150 ease-in-out">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium">Amount</label>
                        <input type="number" name="amount" placeholder="Enter amount"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500 focus:border-blue-500 p-3 transition duration-150 ease-in-out"
                            required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium">Date</label>
                        <input type="date" name="date"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500 focus:border-blue-500 p-3 transition duration-150 ease-in-out"
                            required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium">Note</label>
                        <input type="text" name="note" placeholder="Enter note"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-blue-500 focus:border-blue-500 p-3 transition duration-150 ease-in-out">
                    </div>
                    <hr class="my-4">
                    <div class="flex justify-end">
                        <button type="button" onclick="addModal.close()"
                            class="mr-2 text-gray-500 hover:text-gray-700 transition duration-150 ease-in-out">Close</button>
                        <button type="submit"
                            class="bg-blue-600 text-white font-semibold py-2 px-4 rounded-md hover:bg-blue-700 transition duration-200">Add</button>
                    </div>
                </form>
            </div>
        </dialog>
</x-app-layout>
