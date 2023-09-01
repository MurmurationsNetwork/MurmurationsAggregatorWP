import React from 'react'
import TableRow from './TableRow'
import PropTypes from 'prop-types'

function Table({ tableList, selectedIds, onSelectAll, onSelect }) {
  const isAllSelected = selectedIds.length === tableList.length

  return (
    <table>
      <thead>
        <tr>
          <th>
            <input
              type="checkbox"
              checked={isAllSelected}
              onChange={onSelectAll}
            />
          </th>
          <th className="text-center">ID</th>
          <th className="text-center">Name</th>
          <th className="text-center">Profile URL</th>
          <th className="text-center">Current Status</th>
        </tr>
      </thead>
      <tbody>
        {tableList.map(response => (
          <TableRow
            key={response.id}
            response={response}
            isSelected={selectedIds.includes(response.id)}
            onSelect={onSelect}
          />
        ))}
      </tbody>
    </table>
  )
}

Table.propTypes = {
  tableList: PropTypes.arrayOf(PropTypes.object).isRequired,
  selectedIds: PropTypes.arrayOf(PropTypes.number).isRequired,
  onSelectAll: PropTypes.func.isRequired,
  onSelect: PropTypes.func.isRequired
}

export default Table